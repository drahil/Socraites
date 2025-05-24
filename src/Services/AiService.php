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
            
            Start by reading the provided context carefully. If any file referenced in the diff is missing from the context, clearly mention which files are missing.
            
            Assume all code changes are part of a single feature or task. Use the provided framework (if mentioned) to guide your analysis and understanding.
            
            Then, review the following Git diff with these steps:
            
            1. **File Lists**
                - List all files changed in the diff.
                - List all files available in the provided context.
            
            2. **Overall Summary**
                - Summarize the goal of the change based on the diff. Focus on what the feature or fix is trying to achieve.
            
            3. **Code Review**
                - Point out any issues or bugs you notice.
                - Suggest improvements to code quality, design, or maintainability.
                - Note adherence (or lack thereof) to best practices and framework conventions.
            
            4. **Per-File Feedback**
                - For each changed file:
                    - Summarize the changes.
                    - List issues, suggestions, major issues, and minor issues.
                    - If a file has large or complex changes, suggest relevant design patterns or refactoring strategies.
            
            5. **Commit Message**
                - Propose a concise and clear Git commit message that captures the intent of the changes.
            
            If `verbose mode` is enabled, include more detailed and in-depth suggestions.
            
            Your response must be in JSON format and follow this structure:
            
            {
                "files": [
                    {
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
                    },
                    {
                        "name": "file2.php",
                        "summary": "Summary of changes",
                        "issues": [
                            "Issue 1"
                        ],
                        "suggestions": [
                            "Suggestion 1"
                        ]
                    }
                ],
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

        $payload = $this->addInfoFromConfig($payload, $framework);

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

    /**
     * Adds additional information to the payload based on the configuration.
     *
     * @param array $payload
     * @param string|null $framework
     * @return array
     */
    private function addInfoFromConfig(array $payload, ?string $framework = null): array
    {
        $framework = $framework ?: socraites_config('framework');
        if ($framework) {
            $payload['messages'][] = [
                'role' => 'user',
                'content' => "Framework: $framework",
            ];
        }

        $verbose = socraites_config('verbose');
        if ($verbose) {
            $payload['messages'][] = [
                'role' => 'user',
                'content' => "Verbose mode is enabled.",
            ];
        }

        return $payload;
    }
}
