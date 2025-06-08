<?php

namespace drahil\Socraites\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AiService
{
    protected Client $client;

    public function __construct(
        protected string $token,
        protected array $payload = [],
        protected string $aiResponse = '',
        protected array $previousConversation = []
    ) {
        $this->client = new Client();
    }

    /**
     * Initialize the payload for the AI service.
     *
     * @return AiService
     */
    public function buildPayload(): AiService
    {
        $this->payload = [];

        if (! empty($this->previousConversation)) {
            $this->payload['messages'][] = [
                'role' => 'user',
                'content' => 'Previous conversation: ' . json_encode($this->previousConversation, JSON_PRETTY_PRINT),
            ];
        }

        return $this;
    }

    /**
     * Set the model to be used for the AI service.
     *
     * @param string $model The model name, e.g., 'gpt-3.5-turbo'.
     * @return AiService
     */
    public function usingModel(string $model): AiService
    {
        $this->payload['model'] = $model;

        return $this;
    }

    /**
     * Add a system prompt to the AI service payload.
     *
     * @param string $prompt The system prompt to be added.
     * @return AiService
     */
    public function withPrompt(string $prompt): AiService
    {
        $this->payload['messages'][] = [
            'role' => 'system',
            'content' => $prompt,
        ];

        return $this;
    }

    /**
     * Add a user message to the AI service payload.
     *
     * @param string $key The key for the user message.
     * @param string $message The user message content.
     * @return AiService
     */
    public function withUserMessage(string $key, string $message): AiService
    {
        $this->payload['messages'][] = [
            'role' => 'user',
            'content' => $key . ': ' . $message,
        ];

        return $this;
    }

    /**
     * Set the temperature for the AI response.
     *
     * @param float $temperature The temperature value, typically between 0 and 1.
     * @return AiService
     */
    public function withTemperature(float $temperature): AiService
    {
        $this->payload['temperature'] = $temperature;

        return $this;
    }

    /**
     * Get the AI response based on the provided payload.
     *
     * @return string The content of the AI response.
     * @throws GuzzleException If there is an error with the HTTP request.
     */
    public function getResponse(): string
    {
        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ],
            'json' => $this->payload,
        ]);

        $body = $response->getBody();
        $result = json_decode($body, true);

        $this->aiResponse = $result['choices'][0]['message']['content'] ?? '';

        return $this->aiResponse;
    }

    /**
     * Get the conversation including user messages and AI response.
     *
     * @return AiService
     */
    public function withPreviousConversation(): AiService
    {
        $this->previousConversation = [
            'user_messages' => $this->payload['messages'] ?? [],
            'ai_response' => $this->aiResponse,
        ];

        return $this;
    }
}
