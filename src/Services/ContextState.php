<?php

namespace drahil\Socraites\Services;

class ContextState
{
    public function __construct(
        public array $context = [],
        public array $processedFiles = [],
        public int $totalSize = 0,
        public array $fileScores = [],
        public array $changedFiles = []
    ) {}

    public function addToContext(string $file, string $content): void
    {
        $this->context[$file] = $content;
        $this->processedFiles[$file] = true;
        $this->totalSize += strlen($content);
    }
}