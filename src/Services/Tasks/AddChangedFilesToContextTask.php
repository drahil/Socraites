<?php

declare(strict_types=1);

namespace drahil\Socraites\Services\Tasks;

use drahil\Socraites\Services\ContextState;
use RuntimeException;

class AddChangedFilesToContextTask implements ContextTaskInterface
{
    /**
     * Execute the task to add changed files to the context.
     *
     * @param ContextState $state
     * @return void
     */
    public function execute(ContextState $state): void
    {
        foreach ($state->changedFiles as $file) {
            $fileContent = file_get_contents($file);

            if ($fileContent === false) {
                throw new RuntimeException("Failed to read file: $file");
            }

            $state->addToContext($file, $fileContent);
        }
    }
}