<?php

namespace drahil\Socraites\Services;

use GuzzleHttp\Client;

class AiService
{
    protected string $token;
    protected Client $client;

    public function __construct(string $token)
    {
        $this->client = new Client();
        $this->token = $token;
    }

    public function getCodeReview(string $gitDiff, array $context): string
    {
        $content = <<<EOT
            You are an expert code reviewer.
            
            First, carefully read the provided context. If any file context is missing, mention which ones.
            
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
            
            Be concise and structured in your feedback.
            EOT;

        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
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
            ],
        ]);

        $body = $response->getBody();
        $result = json_decode($body, true);

        return $result['choices'][0]['message']['content'];
    }
}
