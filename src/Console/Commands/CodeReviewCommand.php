<?php

declare(strict_types=1);

namespace drahil\Socraites\Console\Commands;

use drahil\Socraites\Console\Formatters\OutputFormatter;
use drahil\Socraites\Console\QuotePrinter;
use drahil\Socraites\Services\AiService;
use drahil\Socraites\Services\ChangedFilesService;
use drahil\Socraites\Services\ContextBuilder;
use drahil\Socraites\Services\Tools\ProvideCodeReviewTool;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class CodeReviewCommand extends Command
{
    protected AiService $aiService;

    public function __construct(
        private readonly ChangedFilesService $changedFilesService,
        private readonly OutputFormatter $formatter,
        private readonly QuotePrinter $quotePrinter,
    ) {
        parent::__construct('code-review');

        $this->aiService = new AiService(config('socraites.openai_api_key'));
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Perform an AI code review');
    }

    /**
     * This command performs an AI code review on the current git repository.
     * It retrieves the changed code and files from the git repository,
     * builds a context from the changed files,
     * and then uses the AI service to generate a code review.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $changedCode = $this->changedFilesService->getGitDiff();
        $changedFiles = $this->changedFilesService->getChangedFiles();

        $this->quotePrinter->printQuote($output);

        $contextBuilder = new ContextBuilder(
            $this->aiService,
            $changedCode,
            $changedFiles,
        );

        $context = $contextBuilder->build();

        if (! $context) {
            $this->formatter->printError();
            return Command::FAILURE;
        }

        $this->getCodeReview($changedCode, $context);

        $io = new SymfonyStyle($input, $output);
        $this->continueConversation($io);

        $this->formatter->printThankYouMessage();

        return Command::SUCCESS;
    }

    /**
     * Get the code review from the AI service based on the changed code and context.
     *
     * @param string $changedCode The code that has changed.
     * @param array $context The context built from the changed files.
     */
    private function getCodeReview(string $changedCode, array $context): void
    {
        try {
            $codeReview = $this->aiService
                ->buildPayload()
                ->usingModel(socraites_config('openai_model'))
                ->withPrompt(config('socraites.prompts.code_review_message'))
                ->withUserMessage('Git diff', $changedCode)
                ->withUserMessage('Context', json_encode($context, JSON_PRETTY_PRINT))
                ->withTool(new ProvideCodeReviewTool())
                ->withTemperature(socraites_config('temperature', 0.2))
                ->getResponse();
        } catch (Throwable $e) {
            $this->formatter->printError();
            return;
        }

        $this->formatter->setResponse(json_decode($codeReview, true));
        $this->formatter->print();
    }

    /**
     * Continues the conversation with the AI after the initial code review.
     *
     * @param SymfonyStyle $io The SymfonyStyle instance for input/output.
     */
    private function continueConversation(SymfonyStyle $io): void
    {
        while (true) {
            $questionForAi = $io->ask(
                'Do you have a question about the code review?',
                'no',
                fn($answer) => strtolower($answer)
            );

            if (in_array($questionForAi, ['no', 'n'], true)) {
                break;
            }

            try {
                $aiAnswer = $this->aiService
                    ->withPreviousConversation()
                    ->buildPayload()
                    ->usingModel(socraites_config('openai_model'))
                    ->withPrompt(socraites_config('question_prompt'))
                    ->withUserMessage('Question', $questionForAi)
                    ->withTemperature(socraites_config('temperature', 0.2))
                    ->getResponse();
            } catch (Throwable $e) {
                $this->formatter->printError();
                break;
            }

            $this->formatter->setResponse(json_decode($aiAnswer, true));
            $this->formatter->printSimpleAnswer();
        }
    }
}
