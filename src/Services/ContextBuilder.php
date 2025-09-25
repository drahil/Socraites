<?php

declare(strict_types=1);

namespace drahil\Socraites\Services;

use drahil\Socraites\Services\Tools\RequestCodeContextTool;
use drahil\Socraites\Services\Tools\ValidateDeletedCodeTool;
use Exception;
use GuzzleHttp\Exception\GuzzleException;

class ContextBuilder
{
    public function __construct(
        protected AiService $aiService, 
        protected string $changedCode, 
        protected array $changedFiles,
        protected ?DeletedCodeAnalyzer $deletedCodeAnalyzer = null
    ) {
        $this->deletedCodeAnalyzer = $deletedCodeAnalyzer ?? new DeletedCodeAnalyzer();
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

         // Validate deleted code if there are any deletions
         $deletedCodeValidation = $this->validateDeletedCode();
         if ($deletedCodeValidation) {
             $context['deleted_code_validation'] = $deletedCodeValidation;
         }

         return $context;
     }

    /**
     * Validate deleted code using AI analysis.
     *
     * @return array|null Deleted code validation results or null if no significant deletions
     * @throws GuzzleException
     */
    private function validateDeletedCode(): ?array
    {
        $deletedCode = $this->deletedCodeAnalyzer->extractDeletedCode($this->changedCode);
        
        if (empty($deletedCode)) {
            return null; // No deletions found
        }

        // Filter out trivial deletions (like single line comments, whitespace, etc.)
        $significantDeletions = array_filter($deletedCode, function($deletion) {
            return !empty($deletion['name']) && strlen(trim($deletion['code_snippet'] ?? '')) > 10;
        });

        if (empty($significantDeletions)) {
            return null; // No significant deletions found
        }

        try {
            $validationResult = $this->aiService
                ->buildPayload()
                ->usingModel(socraites_config('openai_model'))
                ->withPreviousConversation()
                ->withPrompt(config('socraites.prompts.deleted_code_validation'))
                ->withTool(new ValidateDeletedCodeTool())
                ->withUserMessage('Deleted Code Analysis', json_encode($significantDeletions, JSON_PRETTY_PRINT))
                ->withUserMessage('Git Diff', $this->changedCode)
                ->withTemperature(socraites_config('temperature', 0.2))
                ->getToolResponse();

            return $validationResult ? json_decode($validationResult, true) : null;
        } catch (Exception $e) {
            // Log error but don't fail the entire process
            return [
                'error' => 'Failed to validate deleted code: ' . $e->getMessage(),
                'deleted_items_count' => count($significantDeletions)
            ];
        }
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
