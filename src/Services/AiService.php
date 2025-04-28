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

    public function getCodeReview(string $gitDiff): string
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
                        'content' => 'You are an expert code reviewer. Review the following git diff and suggest improvements, catch bugs, and comment on best practices.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $gitDiff,
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
