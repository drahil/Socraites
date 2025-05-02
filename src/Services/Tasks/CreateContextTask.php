<?php

namespace drahil\Socraites\Services\Tasks;

use drahil\Socraites\Services\ClassMapService;
use drahil\Socraites\Services\ContextState;

class CreateContextTask implements ContextTaskInterface
{
    public function __construct(
        private ClassMapService $classMapService,
        private int $maxContextSize = 100 * 1024,
        private array $namespaceFilters = []
    ) {}

    /**
     * Execute the task to create context from files.
     *
     * @param ContextState $state
     * @return void
     */
    public function execute(ContextState $state): void
    {
        foreach (array_keys($state->fileScores) as $file) {
            if (isset($state->processedFiles[$file])) {
                continue;
            }

            if (! empty($this->namespaceFilters) && !$this->matchesNamespaceFilters($file)) {
                continue;
            }

            $filePath = $this->classMapService->getFilePathForClass($file);

            if (! $filePath || ! file_exists($filePath)) {
                continue;
            }

            $fileContent = file_get_contents($filePath);
            $fileSize = strlen($fileContent);

            if ($state->totalSize + $fileSize > $this->maxContextSize) {
                echo "Context size limit reached. Skipping file: $file\n";
                continue;
            }

            $state->addToContext($file, $fileContent);
        }
    }

    /**
     * Check if the class name matches any of the namespace filters
     *
     * @param string $className
     * @return bool
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