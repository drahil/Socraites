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
        return config('socraites.prompts.code_review_message');
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
                'context' => ['type' => 'array', 'items' => ['type' => 'string']],
                'overall_summary' => ['type' => 'string'],
                'commit_message' => ['type' => 'string'],
            ],
            'required' => ['files', 'overall_summary', 'code_review_message'],
        ];
    }
}
