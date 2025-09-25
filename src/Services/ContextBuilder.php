<?php

declare(strict_types=1);

namespace drahil\Socraites\Services;

use drahil\Socraites\Services\Tools\RequestCodeContextTool;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class ContextBuilder
{
    public function __construct(protected AiService $aiService, protected string $changedCode, protected array $changedFiles)
    {
    }

    /**
     * Build the context for the AI service based on the changed code and files.
     *
     * @throws GuzzleException
     */
    public function build(): array
     {
         $context = [];
         $maxRequests = 2;
         $requestCount = 0;

         try {
             do {
                 $aiRequests = $this->aiService
                     ->buildPayload()
                     ->usingModel(socraites_config('openai_model'))
                     ->withPreviousConversation()
                     ->withPrompt(config('socraites.prompts.initial_message'))
                     ->withTool(new RequestCodeContextTool())
                     ->withUserMessage('Git diff', $this->changedCode)
                     ->withUserMessage('Changed files', json_encode($this->changedFiles, JSON_PRETTY_PRINT))
                     ->withTemperature(socraites_config('temperature', 0.2))
                     ->getToolResponse();

                 if (! $aiRequests) break;

                 $contextChunk = $this->getCodeAiRequested($aiRequests);
                 $context[] = $contextChunk;

                 $this->aiService
                     ->withPreviousConversation()
                     ->buildPayload()
                     ->usingModel(socraites_config('openai_model'))
                     ->withPrompt(config('socraites.prompts.initial_message'))
                     ->withUserMessage('Here is the code you requested', implode("\n\n", $contextChunk))
                     ->withTemperature(socraites_config('temperature', 0.2))
                     ->getResponse();

                 $context[] = $contextChunk;

                 $requestCount++;
             } while ($requestCount < $maxRequests);
         } catch (Exception $e) {
             return [];
         }

         return $context;
     }

    /**
     * Get the code requested by the AI based on the AI requests.
     *
     * @param string $aiRequests The AI requests in JSON format.
     * @return array The code snippets requested by the AI.
     * @throws GuzzleException
     */
    private function getCodeAiRequested(string $aiRequests): array
    {
        $aiRequests = json_decode($aiRequests, true);

        $code = [];
        $contextRequests = $aiRequests['code_context_requests'] ?? [];

        if ($contextRequests) {
            foreach ($contextRequests as $class => $functions) {
                $code[] = \DB::table('code_chunks')
                    ->where('class_name', $class)
                    ->whereIn('method_name', $functions)
                    ->select('code')
                    ->get();
            }
        }

        $semanticContextRequests = $aiRequests['semantic_context_requests'];
        $semanticFoundCode = [];

        foreach ($semanticContextRequests as $request) {
            $embedding = $this->aiService
                ->buildPayload()
                ->usingModel('text-embedding-3-small')
                ->withInput($request)
                ->getEmbedding();

            $semanticFoundCode[] = \DB::table('code_chunks')
                ->select('code')
                ->orderByRaw('embedding <-> ?', [json_encode($embedding)])
                ->limit(5)
                ->get();
        }

        return array_merge($code, $semanticFoundCode);
    }
}
