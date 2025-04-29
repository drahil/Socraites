<?php

namespace drahil\Socraites\Services\Tasks;

use drahil\Socraites\Services\ContextState;

class CreateContextTask implements ContextTaskInterface
{
    private int $maxContextSize;

    public function __construct(int $maxContextSize = 100 * 1024)
    {
        $this->maxContextSize = $maxContextSize;
    }

    public function execute(ContextState $state): void
    {
        foreach (array_keys($state->fileScores) as $file) {
            if (isset($state->processedFiles[$file])) {
                continue;
            }
            if (! str_starts_with($file, 'drahil\\Socraites\\')) {
                continue;
            }

            $filePath = $this->classToFilePath($file);
            $fileContent = file_get_contents($filePath);
            $fileSize = strlen($fileContent);

            if ($state->totalSize + $fileSize > $this->maxContextSize) {
                echo "Context size limit reached. Stopping at $file.\n";
                continue;
            }

            $state->addToContext($file, $fileContent);
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
