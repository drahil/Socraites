<?php

declare(strict_types=1);

namespace drahil\Socraites\Services;

use InvalidArgumentException;
use Symfony\Component\Console\Style\SymfonyStyle;

class SocraitesConfigBuilder
{
    private SymfonyStyle $io;

    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    /**
     * Write the Socraites configuration to a JSON file.
     *
     */
    public function build(): void
    {
        $socraitesJson = [
            'maximum_context_size' => $this->askContextSize(),
            'ignore_patterns' => $this->askIgnorePatterns(),
            'extensions' => $this->askExtensions(),
            'openai_model' => $this->askOpenAiModel(),
            'openai_temperature' => $this->askOpenAiTemperature(),
            'question_prompt' => $this->askQuestionPrompt()
        ];

        file_put_contents('.socraites.json', json_encode($socraitesJson, JSON_PRETTY_PRINT));
    }

    /**
     * Ask the user for the maximum context size.
     *
     * @return int The maximum context size in KB.
     */
    private function askContextSize(): int
    {
        return $this->io->ask(
            'By default the maximum context size is 100 KB. You can change it to a value between 1 and 1024 KB.',
            "100",
            function ($value) {
                if (! is_numeric($value) || $value <= 0) {
                    throw new InvalidArgumentException('The maximum context size must be a positive integer.');
                }
                return (string) $value * 1024;
            }
        );
    }

    /**
     * Ask the user for file patterns to ignore.
     *
     * @return array The list of file patterns to ignore.
     */
    private function askIgnorePatterns(): array
    {
        $value = $this->io->ask(
            'Enter any file patterns to ignore (comma-separated, e.g., "tests/*,vendor/*")',
            'storage/*'
        );
        return array_map('trim', explode(',', $value));
    }

    /**
     * Ask the user for file extensions to include.
     *
     * @return array The list of file extensions to include.
     */
    private function askExtensions(): array
    {
        $value = $this->io->ask(
            'Enter any file extensions to include (comma-separated, e.g., "php,js,css")',
            'php,js,css'
        );
        return array_map('trim', explode(',', $value));
    }

    /**
     * Ask the user for the OpenAI model to use.
     *
     * @return string The OpenAI model name.
     */
    private function askOpenAiModel(): string
    {
        return $this->io->ask(
            'Enter the OpenAI model to use',
            'gpt-4-turbo',
            function ($value) {
                if (empty($value)) {
                    throw new InvalidArgumentException('The OpenAI model cannot be empty.');
                }
                return $value;
            }
        );
    }

    /**
     * Ask the user for the OpenAI temperature setting.
     *
     * @return string The OpenAI temperature value.
     */
    private function askOpenAiTemperature(): string
    {
        return $this->io->ask(
            'Enter the OpenAI temperature',
            (string) 0.2,
            function ($value) {
                if (! is_numeric($value) || $value < 0 || $value > 1) {
                    throw new InvalidArgumentException('The OpenAI temperature must be a number between 0 and 1.');
                }
                return (string) $value;
            }
        );
    }

    /**
     * Ask the user for the prompt to ask questions about the code review.
     *
     * @return string The question prompt.
     */
    private function askQuestionPrompt(): string
    {
        return $this->io->ask(
            'Enter the prompt for asking questions about the code review',
            'Using the information from previous_conversation array, answer the question strictly in valid JSON format.'
                .  'Do not include any extra text or explanation. Only return a valid JSON object or array',
            function ($value) {
                if (empty($value)) {
                    throw new InvalidArgumentException('The question prompt cannot be empty.');
                }
                return $value;
            }
        );
    }
}
