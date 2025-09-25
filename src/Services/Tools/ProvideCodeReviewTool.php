<?php

declare(strict_types=1);

namespace drahil\Socraites\Services\Tools;

class ProvideCodeReviewTool extends BaseTool
{
    public function getName(): string
    {
        return 'provide_code_review';
    }

    public function getDescription(): string
    {
        return 'Provide a structured code review analysis. Use this tool to deliver comprehensive feedback '
            . 'on code changes including security issues, performance concerns, best practices violations, '
            . 'and improvement suggestions. Format your response with file-by-file analysis and actionable recommendations.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'files' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'issues' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'suggestions' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'major_issues' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'minor_issues' => ['type' => 'array', 'items' => ['type' => 'string']]
                        ],
                        'required' => ['name', 'summary']
                    ]
                ],
                'context' => [
                    'type' => 'array', 
                    'items' => ['type' => 'string'],
                    'description' => 'List of context files or functions that were analyzed'
                ],
                'overall_summary' => [
                    'type' => 'string',
                    'description' => 'High-level summary of the changes and their purpose'
                ],
                'critical_issues' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Security vulnerabilities, performance bottlenecks, breaking changes, and unsafe deletions'
                ],
                'deleted_code_assessment' => [
                    'type' => 'object',
                    'properties' => [
                        'has_deletions' => ['type' => 'boolean'],
                        'overall_safety' => ['type' => 'string', 'enum' => ['safe', 'caution', 'unsafe']],
                        'high_risk_deletions' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'recommended_verifications' => ['type' => 'array', 'items' => ['type' => 'string']]
                    ],
                    'description' => 'Assessment of deleted code safety (if deleted code validation was provided in context)'
                ],
                'recommended_testing' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Suggested tests and scenarios to verify the changes'
                ],
                'commit_message' => [
                    'type' => 'string',
                    'description' => 'Proposed Git commit message in conventional commit format'
                ],
            ],
            'required' => ['files', 'context', 'overall_summary', 'commit_message'],
        ];
    }
}
