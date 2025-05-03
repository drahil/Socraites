<?php

namespace drahil\Socraites\Services\Tasks;

use drahil\Socraites\Parsers\FileParser;
use drahil\Socraites\Services\ContextState;
use Exception;

class ProcessFilesTask implements ContextTaskInterface
{
    public function __construct(
        private FileParser $fileParser
    ) {}

    /**
     * Execute the task to process files and update the context state.
     *
     * @param ContextState $state
     * @return void
     */
    public function execute(ContextState $state): void
    {
        foreach ($state->changedFiles as $file) {
            try {
                $parsed = $this->fileParser->parse($file);
                $dependencies = array_merge(
                    $parsed['imports'],
                    $parsed['extends']
                );

                foreach ($dependencies as $dependency) {
                    if (isset($state->processedFiles[$dependency]) || in_array($dependency, $state->changedFiles)) {
                        continue;
                    }

                    $score = $this->calculateRelevanceScore($dependency, $file, $parsed);
                    $state->fileScores[$dependency] = $score;
                }

                arsort($state->fileScores);
                foreach (array_keys($state->fileScores) as $dependencyFile) {
                    if (! isset($state->processedFiles[$dependencyFile]) && !in_array($dependencyFile, $state->changedFiles)) {
                        $state->changedFiles[] = $dependencyFile;
                    }
                }
            } catch (Exception $exception) {
                echo "Error parsing file $file: " . $exception->getMessage() . "\n";
            }
        }
    }

    /**
     * Calculate the relevance score for a file based on its dependencies and usage.
     *
     * @param string $filePath The path to the file.
     * @param string $sourceFile The source file that references this file.
     * @param array $parseResult The result of parsing the source file.
     * @return int The calculated relevance score.
     */
    private function calculateRelevanceScore(string $filePath, string $sourceFile, array $parseResult): int
    {
        $score = 0;

        if (in_array($filePath, $parseResult['imports'])) {
            $score += socraites_config('scores.import');
        }

        if (in_array($filePath, $parseResult['extends'])) {
            $score += socraites_config('scores.extends');
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

}