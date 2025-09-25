<?php

declare(strict_types=1);

namespace drahil\Socraites\Services\Tools;

class ValidateDeletedCodeTool extends BaseTool
{
    public function getName(): string
    {
        return 'validate_deleted_code';
    }

    public function getDescription(): string
    {
        return 'Analyze deleted code (methods, classes, properties) to determine if the deletions are significant and safe. '
            . 'Use this tool to evaluate the impact of removed code and identify potential issues such as: '
            . 'breaking API changes, removal of critical functionality, missing dependency cleanup, '
            . 'or deletion of code that other parts of the system rely on. '
            . 'This validation helps ensure that code deletions are intentional and safe.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'deleted_items' => [
                    'type' => 'array',
                    'description' => 'List of deleted code elements that need validation. '
                        . 'Include methods, classes, properties, constants, or entire files that were removed.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'enum' => ['method', 'class', 'property', 'constant', 'file', 'interface', 'trait'],
                                'description' => 'Type of deleted code element'
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'Name of the deleted element (e.g., method name, class name)'
                            ],
                            'file_path' => [
                                'type' => 'string',
                                'description' => 'File path where the deletion occurred'
                            ],
                            'code_snippet' => [
                                'type' => 'string',
                                'description' => 'The actual deleted code (from git diff)'
                            ],
                            'significance_level' => [
                                'type' => 'string',
                                'enum' => ['low', 'medium', 'high', 'critical'],
                                'description' => 'Assessment of deletion significance'
                            ],
                            'reason' => [
                                'type' => 'string',
                                'description' => 'Explanation of why this deletion is significant or safe'
                            ]
                        ],
                        'required' => ['type', 'name', 'significance_level', 'reason']
                    ]
                ],
                'overall_assessment' => [
                    'type' => 'object',
                    'properties' => [
                        'is_safe' => [
                            'type' => 'boolean',
                            'description' => 'Whether the overall deletions are considered safe'
                        ],
                        'risk_level' => [
                            'type' => 'string',
                            'enum' => ['low', 'medium', 'high', 'critical'],
                            'description' => 'Overall risk level of all deletions combined'
                        ],
                        'recommended_actions' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Recommended actions before proceeding with these deletions'
                        ],
                        'potential_impacts' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Potential impacts on the codebase, users, or system'
                        ]
                    ],
                    'required' => ['is_safe', 'risk_level']
                ],
                'requires_additional_context' => [
                    'type' => 'boolean',
                    'description' => 'Whether additional code context is needed to properly assess the deletions'
                ],
                'context_requests' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Specific code context needed to complete the deletion validation'
                ]
            ],
            'required' => ['deleted_items', 'overall_assessment'],
        ];
    }
}