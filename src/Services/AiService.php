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
                        'content' => 'You are an expert code reviewer. First, carefully read the provided context. Then, review the following git diff based on that context. Suggest improvements, catch bugs, and comment on best practices.',
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
