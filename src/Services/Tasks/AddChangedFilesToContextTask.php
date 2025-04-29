<?php

namespace drahil\Socraites\Services\Tasks;

use drahil\Socraites\Services\ContextState;
use RuntimeException;

class AddChangedFilesToContextTask implements ContextTaskInterface
{
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