<?php

namespace drahil\Socraites\Services;

use drahil\Socraites\Parsers\FileParser;
use Exception;

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
            } catch (Exception $exception) {
                echo "Error parsing file $file: " . $exception->getMessage() . "\n";
            }
        }
    }

    private function calculateRelevanceScore(string $filePath, string $sourceFile, array $parseResult): int
    {
        $score = 0;

        if (in_array($filePath, $parseResult['imports'])) {
            $score += 5;
        }

        if (in_array($filePath, $parseResult['extends'])) {
            $score += 10;
        }

        if (in_array($filePath, array_keys($parseResult['usageCounts'][$sourceFile]))) {
            // Logarithmic scale to avoid classes with many references dominating completely
            $usageCount = $parseResult['usageCounts'][$sourceFile][$filePath];
            if ($usageCount > 0) {
                $score += 5 + min(20, (int)(5 * log($usageCount + 1, 2)));
            }
        }

        return $score;
    }

    private function createContext(): void
    {
        $maxContextSize = 100 * 1024; // ~100KB

        foreach (array_keys($this->fileScores) as $file) {
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
                echo "Context size limit reached. Stopping at $file.\n";
                continue;
            }

            $this->context[$file] = $fileContent;
            $this->processedFiles[$file] = true;
            $this->totalSize += $fileSize;
        }
    }

    // TODO this will be removed once we have a proper class map
    private function classToFilePath(int|string $class): string
    {
        // Remove the top-level namespace (drahil\Socraites\)
        $class = preg_replace('/^drahil\\\\Socraites\\\\/', '', $class);

        // Replace \ with /
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

        // Base path is /src/
        return __DIR__ . '/../' . $relativePath;
    }
}
