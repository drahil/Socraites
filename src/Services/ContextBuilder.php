<?php

namespace drahil\Socraites\Services;

class ContextBuilder
{
    public function buildContext(array $changedFiles): array
    {
        $context = [];

        foreach ($changedFiles as $file) {
            $fileContent = file_get_contents($file);

            if ($fileContent === false) {
                throw new \RuntimeException("Failed to read file: $file");
            }

            $context[$file] = $fileContent;
        }

        return $context;
    }
}