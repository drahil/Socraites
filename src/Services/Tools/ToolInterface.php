<?php

namespace drahil\Socraites\Services\Tools;

interface ToolInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getParametersSchema(): array;
}