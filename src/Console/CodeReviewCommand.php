<?php

namespace drahil\Socraites\Console;

use drahil\Socraites\Console\Formatters\OutputFormatter;
use drahil\Socraites\Services\AiService;
use drahil\Socraites\Services\ContextBuilder;
use drahil\Socraites\Services\ChangedFilesService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CodeReviewCommand extends Command
{
    protected ContextBuilder $contextBuilder;

    public function __construct(
        private readonly ChangedFilesService $changedFilesService,
        private readonly AiService $aiService,
        private readonly OutputFormatter $formatter,
        private readonly QuotePrinter $quotePrinter
    ) {
        parent::__construct('code-review');
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Perform an AI code review')
            ->addOption(
                'framework',
                null,
                InputOption::VALUE_OPTIONAL,
                'Framework that is used in the project'
            )->addOption(
                'verbose',
                null,
                InputOption::VALUE_NONE,
                'Enable verbose output'
            );
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
        [$framework, $verbose] = $this->getValuesFromInput($input);

        $changedCode = $this->changedFilesService->getGitDiff();
        $changedFiles = $this->changedFilesService->getChangedFiles();

        $this->quotePrinter->printQuote();

        $this->contextBuilder = new ContextBuilder($changedFiles);
        $context = $this->contextBuilder->buildContext();

        $codeReview = $this->aiService
            ->buildPayload()
            ->usingModel(socraites_config('openai_model'))
            ->withPrompt(socraites_config('code_review_prompt'))
            ->withUserMessage('Git diff', $changedCode)
            ->withUserMessage('Context', json_encode($context, JSON_PRETTY_PRINT))
            ->withUserMessage('Framework', $framework ?: 'None')
            ->withUserMessage('Verbose', $verbose ? 'Enabled' : 'Disabled')
            ->withTemperature(socraites_config('temperature', 0.2))
            ->getResponse();

        $this->formatter->setReview(json_decode($codeReview, true));
        $this->formatter->print();

        return Command::SUCCESS;
    }

    /**
     * Get the framework and verbose options from the input.
     *
     * @param InputInterface $input
     * @return array
     */
    private function getValuesFromInput(InputInterface $input): array
    {
        $framework = $input->getOption('framework') ?: socraites_config('framework');
        $verbose = $input->getOption('verbose') ?: socraites_config('verbose');

        return [$framework, $verbose];
    }
}
