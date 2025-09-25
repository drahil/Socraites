<?php

declare(strict_types=1);

namespace drahil\Socraites\Services\Tools;

class RequestCodeContextTool extends BaseTool
{
    public function getName(): string
    {
        return 'request_code_context';
    }

    public function getDescription(): string
    {
        return config('socraites.prompts.code_review_message');
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code_context_requests' => [
                    'type' => 'object',
                    'description' => 'Use when you know the class name and are asking for specific methods, constants, or properties.'
                        . ' Dictionary where key = class name (or FQCN), value = array of method/property names'
                        . 'Repeat this until you have enough context to provide a thorough code review, but make sure'
                        . 'not to ask for the same class or method multiple times. If you do not need context, send an empty object.',
                    'additionalProperties' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                ],
                'semantic_context_requests' => [
                    'type' => 'array',
                    'description' => 'Use when you don\'t know the exact class or method, but need logic related to a particular concept.'
                        . ' Array of plain English descriptions.'
                        . ' Repeat this until you have enough context to provide a thorough code review.',
                    'items' => ['type' => 'string'],
                ]
            ],
            'required' => [],
        ];
    }
}
