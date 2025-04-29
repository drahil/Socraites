<?php

namespace drahil\Socraites\Services;

use drahil\Socraites\Parsers\FileParser;

class ContextBuilder
{
    protected array $context = [];
    protected array $processedFiles = [];
    protected int $totalSize = 0;
    protected FileParser $fileParser;
    protected array $fileScores = [];
    protected array $changedFiles = [];

    public function __construct(array $changedFiles)
    {
        $this->fileParser = new FileParser();
        $this->changedFiles = $changedFiles;
    }

    public function buildContext(): array
    {
        $this->addChangedFilesToContext();
        $this->processFiles();
        $this->createContext();

        return $this->context;
    }

    private function addChangedFilesToContext(): void
    {
        foreach ($this->changedFiles as $file) {
            $fileContent = file_get_contents($file);

            if ($fileContent === false) {
                throw new \RuntimeException("Failed to read file: $file");
            }

            $this->context[$file] = $fileContent;
            $this->processedFiles[$file] = true;
            $this->totalSize += strlen($fileContent);
        }
    }

    private function processFiles(): void
    {
        foreach ($this->changedFiles as $file) {
            try {
                $parsed = $this->fileParser->parse($file);
                $dependencies = array_merge(
                    $parsed['imports'],
                    $parsed['extends']
                );

                foreach ($dependencies as $dependency) {
                    if (isset($this->processedFiles[$dependency]) || in_array($dependency, $this->changedFiles)) {
                        continue;
                    }

                    $score = $this->calculateRelevanceScore($dependency, $file, $parsed);
                    $this->fileScores[$dependency] = $score;
                }

                arsort($this->fileScores);
                foreach (array_keys($this->fileScores) as $dependencyFile) {
                    if (!isset($this->processedFiles[$dependencyFile]) && !in_array($dependencyFile, $this->changedFiles)) {
                        $this->changedFiles[] = $dependencyFile;
                    }
                }
            } catch (\Exception $exception) {
            }
        }
    }

    private function calculateRelevanceScore(string $filePath, string $sourceFile, array $parseResult): int
    {
        $score = 0;
        $filePath = $this->normalizeClassName($filePath);
        echo "Calculating relevance score for $filePath\n";
        echo "Source file: $sourceFile\n";
        echo "Parse result: " . json_encode($parseResult) . "\n";

        if (isset($parseResult['imports'][$filePath])) {
            $score += 10;
        }

        if (isset($parseResult['extends'][$filePath])) {
            $score += 5;
        }

        if (isset($parseResult['usageCounts'][$filePath])) {
            // Logarithmic scale to avoid classes with many references dominating completely
            $usageCount = $parseResult['usageCounts'][$filePath];
            if ($usageCount > 0) {
                $score += 5 + min(20, (int)(5 * log($usageCount + 1, 2)));
            }
        }

        return $score;
    }

    private function createContext(): void
    {
        $maxContextSize = 350 * 1024; // ~350KB

        foreach (array_keys($this->fileScores) as $file) {
//            echo "Processing file: $file\n";
//            echo "Total size: {$this->totalSize}\n";
//            echo "Max context size: $maxContextSize\n";
//            echo "Current file size: " . strlen($this->context[$file] ?? '') . "\n";
            echo "File scores: " . json_encode($this->fileScores) . "\n";
            if (isset($this->processedFiles[$file])) {
                continue;
            }
            if (! str_starts_with($file, 'drahil\\Socraites\\')) {
                continue;
            }

            $filePath = $this->classToFilePath($file);
            $fileContent = file_get_contents($filePath);
            $fileSize = strlen($fileContent);

            if ($this->totalSize + $fileSize > $maxContextSize) {
                continue;
            }

            $this->context[$file] = $fileContent;
            $this->processedFiles[$file] = true;
            $this->totalSize += $fileSize;
        }
    }

    private function classToFilePath(int|string $class): string
    {
        // Remove the top-level namespace (drahil\Socraites\)
        $class = preg_replace('/^drahil\\\\Socraites\\\\/', '', $class);

        // Replace \ with /
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

        // Base path is /src/
        return __DIR__ . '/../' . $relativePath;
    }

    private function normalizeClassName(string $className): string
    {
        // Remove any potential escape sequences
        return str_replace('\\', '\\\\', $className);
    }
}
