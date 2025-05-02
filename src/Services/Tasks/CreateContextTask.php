<?php

namespace drahil\Socraites\Services\Tasks;

use drahil\Socraites\Services\ClassMapService;
use drahil\Socraites\Services\ContextState;

class CreateContextTask implements ContextTaskInterface
{
    private int $maxContextSize;
    private ClassMapService $classMapService;
    private array $namespaceFilters = [];

    public function __construct(
        ClassMapService $classMapService,
        int $maxContextSize = 100 * 1024,
        array $namespaceFilters = []
    ) {
        $this->classMapService = $classMapService;
        $this->maxContextSize = $maxContextSize;
        $this->namespaceFilters = $namespaceFilters;
    }

    public function execute(ContextState $state): void
    {
        foreach (array_keys($state->fileScores) as $file) {
            if (isset($state->processedFiles[$file])) {
                continue;
            }

            if (!empty($this->namespaceFilters) && !$this->matchesNamespaceFilters($file)) {
                continue;
            }

            $filePath = $this->classMapService->getFilePathForClass($file);

            if (!$filePath || !file_exists($filePath)) {
                continue;
            }

            $fileContent = file_get_contents($filePath);
            $fileSize = strlen($fileContent);

            if ($state->totalSize + $fileSize > $this->maxContextSize) {
                echo "Context size limit reached. Stopping at $file.\n";
                continue;
            }

            $state->addToContext($file, $fileContent);
        }
    }

    /**
     * Check if the class name matches any of the namespace filters
     */
    private function matchesNamespaceFilters(string $className): bool
    {
        if (empty($this->namespaceFilters)) {
            return true;
        }

        foreach ($this->namespaceFilters as $namespace) {
            if (str_starts_with($className, $namespace)) {
                return true;
            }
        }

        return false;
    }
}