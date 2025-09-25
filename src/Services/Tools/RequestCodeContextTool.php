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
        return 'Request specific code context from the codebase. Use this tool to gather relevant code snippets, '
            . 'class methods, and related functionality needed for a comprehensive code review. '
            . 'You can request specific classes and methods by name, or use semantic search to find related code patterns.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'code_context_requests' => [
                    'type' => 'object',
                    'description' => 'Request specific classes and their methods/properties by exact name. '
                        . 'Use this when you can identify class names from imports, inheritance, or method calls in the diff. '
                        . 'Key = full class name (e.g., "App\\Services\\UserService"), '
                        . 'Value = array of specific method/property names to retrieve. '
                        . 'Limit to 3-5 most relevant classes to avoid context overload.',
                    'additionalProperties' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'examples' => [
                        [
                            'App\\Http\\Controllers\\UserController' => ['store', 'update', 'destroy'],
                            'App\\Models\\User' => ['fillable', 'rules', 'boot']
                        ]
                    ]
                ],
                'semantic_context_requests' => [
                    'type' => 'array',
                    'description' => 'Search for related code using natural language descriptions. '
                        . 'Use this when you need context about functionality but don\'t know exact class/method names. '
                        . 'Examples: "user authentication logic", "email validation rules", "payment processing workflow". '
                        . 'Keep descriptions specific and focused on the area being changed.',
                    'items' => ['type' => 'string'],
                    'examples' => [
                        'user registration and validation logic',
                        'error handling for database operations',
                        'middleware for API authentication'
                    ]
                ]
            ],
            'required' => [],
        ];
    }
}
