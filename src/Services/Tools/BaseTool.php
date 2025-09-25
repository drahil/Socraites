<?php

declare(strict_types=1);

namespace drahil\Socraites\Services\Tools;

abstract class BaseTool implements ToolInterface
{
    public function getDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => $this->getParametersSchema()
            ]
        ];
    }

    abstract public function getDescription(): string;
}