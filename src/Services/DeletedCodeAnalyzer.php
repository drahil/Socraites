<?php

declare(strict_types=1);

namespace drahil\Socraites\Services;

class DeletedCodeAnalyzer
{
    /**
     * Extract deleted code elements from git diff output.
     *
     * @param string $gitDiff The git diff output
     * @return array Array of deleted code information
     */
    public function extractDeletedCode(string $gitDiff): array
    {
        $deletedCode = [];
        $lines = explode("\n", $gitDiff);
        $currentFile = null;
        $deletedLines = [];
        $isInDeletedBlock = false;

        foreach ($lines as $line) {
            if (preg_match('/^--- a\/(.+)$/', $line, $matches)) {
                $currentFile = $matches[1];
                continue;
            }

            if ($currentFile && ! str_ends_with($currentFile, '.php')) {
                continue;
            }

            if (str_starts_with($line, '-') && !str_starts_with($line, '---')) {
                $deletedLines[] = substr($line, 1); // Remove the '-' prefix
                $isInDeletedBlock = true;
            } elseif ($isInDeletedBlock && (!str_starts_with($line, '-') || str_starts_with($line, '+++'))) {
                if (!empty($deletedLines) && $currentFile) {
                    $deletedCode = array_merge($deletedCode, $this->analyzeDeletedLines($deletedLines, $currentFile));
                }
                $deletedLines = [];
                $isInDeletedBlock = false;
            }
        }

        if (!empty($deletedLines) && $currentFile) {
            $deletedCode = array_merge($deletedCode, $this->analyzeDeletedLines($deletedLines, $currentFile));
        }

        return $deletedCode;
    }

    /**
     * Analyze deleted lines to identify methods, classes, properties, etc.
     *
     * @param array $deletedLines Array of deleted code lines
     * @param string $filePath Path to the file where deletions occurred
     * @return array Array of deleted code elements
     */
    private function analyzeDeletedLines(array $deletedLines, string $filePath): array
    {
        $deletedElements = [];
        $codeBlock = implode("\n", $deletedLines);

        if (preg_match_all('/^[\s]*(?:abstract\s+|final\s+)?class\s+(\w+)/m', $codeBlock, $matches)) {
            foreach ($matches[1] as $className) {
                $deletedElements[] = [
                    'type' => 'class',
                    'name' => $className,
                    'file_path' => $filePath,
                    'code_snippet' => $this->extractRelevantCode($codeBlock, $className),
                ];
            }
        }

        if (preg_match_all('/^[\s]*interface\s+(\w+)/m', $codeBlock, $matches)) {
            foreach ($matches[1] as $interfaceName) {
                $deletedElements[] = [
                    'type' => 'interface',
                    'name' => $interfaceName,
                    'file_path' => $filePath,
                    'code_snippet' => $this->extractRelevantCode($codeBlock, $interfaceName),
                ];
            }
        }

        if (preg_match_all('/^[\s]*trait\s+(\w+)/m', $codeBlock, $matches)) {
            foreach ($matches[1] as $traitName) {
                $deletedElements[] = [
                    'type' => 'trait',
                    'name' => $traitName,
                    'file_path' => $filePath,
                    'code_snippet' => $this->extractRelevantCode($codeBlock, $traitName),
                ];
            }
        }

        if (preg_match_all('/^[\s]*(?:public\s+|protected\s+|private\s+)?(?:static\s+)?function\s+(\w+)\s*\(/m', $codeBlock, $matches)) {
            foreach ($matches[1] as $methodName) {
                // Skip magic methods and constructors for basic deletion checks
                if (str_starts_with($methodName, '__')) {
                    continue;
                }
                
                $deletedElements[] = [
                    'type' => 'method',
                    'name' => $methodName,
                    'file_path' => $filePath,
                    'code_snippet' => $this->extractRelevantCode($codeBlock, $methodName),
                ];
            }
        }

        if (preg_match_all('/^[\s]*(?:public\s+|protected\s+|private\s+)?(?:static\s+)?\$(\w+)/m', $codeBlock, $matches)) {
            foreach ($matches[1] as $propertyName) {
                $deletedElements[] = [
                    'type' => 'property',
                    'name' => '$' . $propertyName,
                    'file_path' => $filePath,
                    'code_snippet' => $this->extractRelevantCode($codeBlock, '$' . $propertyName),
                ];
            }
        }

        if (preg_match_all('/^[\s]*(?:public\s+|protected\s+|private\s+)?const\s+(\w+)/m', $codeBlock, $matches)) {
            foreach ($matches[1] as $constantName) {
                $deletedElements[] = [
                    'type' => 'constant',
                    'name' => $constantName,
                    'file_path' => $filePath,
                    'code_snippet' => $this->extractRelevantCode($codeBlock, $constantName),
                ];
            }
        }

        return $deletedElements;
    }

    /**
     * Extract relevant code snippet around a specific element.
     *
     * @param string $codeBlock The full deleted code block
     * @param string $elementName The element name to extract context for
     * @return string The relevant code snippet
     */
    private function extractRelevantCode(string $codeBlock, string $elementName): string
    {
        $lines = explode("\n", $codeBlock);
        $relevantLines = [];
        $found = false;
        $braceLevel = 0;
        $inFunction = false;

        foreach ($lines as $line) {
            if (!$found && str_contains($line, $elementName)) {
                $found = true;
                $relevantLines[] = $line;
                
                // Check if this is a function/method definition
                if (preg_match('/function\s+' . preg_quote($elementName, '/') . '\s*\(/', $line)) {
                    $inFunction = true;
                }
                
                continue;
            }

            if ($found) {
                $relevantLines[] = $line;
                
                // Track braces for methods/classes
                $openBraces = substr_count($line, '{');
                $closeBraces = substr_count($line, '}');
                $braceLevel += $openBraces - $closeBraces;
                
                // Stop when we've closed all braces (end of method/class)
                if ($inFunction && $braceLevel <= 0 && str_contains($line, '}')) {
                    break;
                }
                
                // Limit context to prevent huge snippets
                if (count($relevantLines) > 50) {
                    $relevantLines[] = '... (truncated)';
                    break;
                }
            }
        }

        return implode("\n", array_slice($relevantLines, 0, 20)); // Limit to 20 lines
    }
}