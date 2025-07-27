<?php

declare(strict_types=1);

namespace drahil\Socraites\Services;

use drahil\Socraites\Services\Tools\ToolInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AiService
{
    protected Client $client;

    private const CHAT_COMPLETION_URL = 'https://api.openai.com/v1/chat/completions';
    private const EMBEDDING_URL = 'https://api.openai.com/v1/embeddings';

    public function __construct(
        protected string $token,
        protected array $payload = [],
        protected string $aiResponse = '',
        protected array $previousConversation = []
    ) {
        $this->client = new Client();
    }

    public function getPayload(): array
    {
        return $this->payload;
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
     * Get the headers required for the AI service requests.
     *
     * @return array The headers including authorization and content type.
     */
    public function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
        ];
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
        $response = $this->client->post(self::CHAT_COMPLETION_URL, [
            'headers' => $this->getHeaders(),
            'json' => $this->payload,
        ]);

        $result = json_decode((string) $response->getBody(), true);

        $this->aiResponse = $result['choices'][0]['message']['tool_calls'][0]['function']['arguments']
            ?? $result['choices'][0]['message']['content']
            ?? '';

        return $this->aiResponse;
    }

    /**
     * Get the response from a tool call in the AI service.
     *
     * @return string The arguments from the tool call response.
     * @throws GuzzleException If there is an error with the HTTP request.
     */
    public function getToolResponse(): string
    {
        $response = $this->client->post(self::CHAT_COMPLETION_URL, [
            'headers' => $this->getHeaders(),
            'json' => $this->payload,
        ]);

        $result = json_decode((string) $response->getBody(), true);

        return $this->aiResponse = $result['choices'][0]['message']['tool_calls'][0]['function']['arguments'] ?? '';
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
            'ai_response' => $this->aiResponse ?? '',
        ];

        return $this;
    }

    /**
     * Set the input for the AI service.
     *
     * @param string $input
     * @return $this
     */
    public function withInput(string $input): AiService
    {
        $this->payload['input'] = $input;

        return $this;
    }

    /**
     * Get the embedding for the provided input.
     *
     * @return array The embedding vector.
     * @throws GuzzleException If there is an error with the HTTP request.
     */
    public function getEmbedding(): array
    {
        $response = $this->client->post(self::EMBEDDING_URL, [
            'headers' => $this->getHeaders(),
            'json' => $this->payload,
        ]);

        $result = json_decode((string) $response->getBody(), true);

        return $result['data'][0]['embedding'];
    }

    /**
     * Add a tool to the AI service payload.
     *
     * @param ToolInterface $tool The tool to be added.
     * @return AiService
     */
    public function withTool(ToolInterface $tool): AiService
    {
        $this->payload['tools'][] = $tool->getDefinition();
        $this->payload['tool_choice'] = [
            'type' => 'function',
            'function' => [
                'name' => $tool->getName(),
            ],
        ];

        return $this;
    }

    /**
     * Add multiple tools to the AI service payload.
     *
     * @param ToolInterface[] $tools An array of tools to be added.
     * @return AiService
     */
    public function withTools(array $tools): AiService
    {
        foreach ($tools as $tool) {
            if ($tool instanceof ToolInterface) {
                $this->withTool($tool);
            }
        }

        return $this;
    }
}
