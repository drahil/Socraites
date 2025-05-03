<?php

namespace drahil\Socraites\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AiService
{
    protected Client $client;

    public function __construct(protected string $token)
    {
        $this->client = new Client();
    }

    /**
     * Generates a code review based on the provided git diff and context.
     *
     * @param string $gitDiff The git diff to be reviewed.
     * @param array $context The context for the code review.
     * @param string|null $framework The framework used in the project (optional).
     * @return string The generated code review.
     * @throws GuzzleException
     */
    public function getCodeReview(string $gitDiff, array $context, ?string $framework = null): string
    {
        $content = <<<EOT
            You are an expert code reviewer.
            
            First, carefully read the provided context. If any file context is missing, mention which ones.
            Try to analyze all code changes as a part of one feature.
            If framework is provided, use it to understand the code better. In your response make sure to mention the framework.
            
            Then, review the following git diff based on the context:
            - List all files that are changed.
            - List all files from the context.
            - Summarize the overall goal of the changes based on the diff.
            
            Next, review the code in the diff:
            - Identify and explain any issues you find.
            - Suggest improvements and highlight potential bugs.
            - Comment on adherence to best practices.
            
            Provide comments per file:
            - If a file has large changes, suggest appropriate design patterns or refactoring strategies.
            
            At the end, suggest a suitable Git commit message summarizing the intent of the changes. Keep it short and clear.
            Be concise and structured in your feedback. Do not go into too much detail. Pay special attention to design patterns and best practices.
            In suggestions, feel free to add what you may think is the best way to do it.
            
            Your response should be JSON. This is the draft:
            {
                "files": {
                    [
                        "name": "file1.php",
                        "summary": "Summary of changes",
                        "issues": [
                            "Issue 1",
                            "Issue 2"
                        ],
                        "suggestions": [
                            "Suggestion 1",
                            "Suggestion 2"
                        ],
                        "major_issues": [
                            "Major issue 1"
                        ],
                        "minor_issues": [
                            "Minor issue 1"
                        ]
                    ],
                    [
                        "name": "file2.php",
                        "summary": "Summary of changes",
                        "issues": [
                            "Issue 1"
                        ],
                        "suggestions": [
                            "Suggestion 1"
                        ]
                    ]
                },
                "context": [
                    "file_from_context_1.php",
                    "file_from_context_2.php"
                ],
                "overall_summary": "Overall summary of the changes",
                "commit_message": "Suggested commit message"
            }
            
            EOT;

        $payload = [
            'model' => 'gpt-4-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $content,
                ],
                [
                    'role' => 'user',
                    'content' => "Context:\n" . json_encode($context, JSON_PRETTY_PRINT),
                ],
                [
                    'role' => 'user',
                    'content' => "Git diff:\n" . $gitDiff,
                ],
            ],
            'temperature' => 0.2,
        ];

        if ($framework) {
            $payload['messages'][] = [
                'role' => 'user',
                'content' => "Framework:\n" . $framework,
            ];
        }

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $body = $response->getBody();
        $result = json_decode($body, true);

        return $result['choices'][0]['message']['content'];
    }
}
