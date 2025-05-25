<?php

namespace drahil\Socraites\Console;

use drahil\Socraites\Console\Formatters\OutputFormatter;
use drahil\Socraites\Services\AiService;
use drahil\Socraites\Services\ContextBuilder;
use drahil\Socraites\Services\ChangedFilesService;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
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
        $framework = $input->getOption('framework');

        $changedCode = $this->changedFilesService->getGitDiff();
        $changedFiles = $this->changedFilesService->getChangedFiles();

        $this->quotePrinter->printQuote();

        $this->contextBuilder = new ContextBuilder($changedFiles);
        $context = $this->contextBuilder->buildContext();

        $codeReview = $this->aiService->getCodeReview($changedCode, $context, $framework);

        $this->formatter->setReview(json_decode($codeReview, true));
        $this->formatter->print();

        return Command::SUCCESS;
    }
}
