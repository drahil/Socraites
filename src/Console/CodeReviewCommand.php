<?php

namespace drahil\Socraites\Console;

use drahil\Socraites\Services\AiService;
use drahil\Socraites\Services\ContextBuilder;
use drahil\Socraites\Services\GitService;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CodeReviewCommand extends Command
{
    protected GitService $gitService;
    protected AiService $aiService;
    protected array $context;
    protected ContextBuilder $contextBuilder;

    public function __construct()
    {
        $this->resolveDependencies();
        parent::__construct('code-review');
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $changedCode = $this->gitService->getGitDiff();
        $changedFiles = $this->gitService->getChangedFiles();

        $this->contextBuilder = new ContextBuilder($changedFiles);
        $context = $this->contextBuilder->buildContext();

        $codeReview = $this->aiService->getCodeReview($changedCode, $context);

        $output->writeln($codeReview);

        return Command::SUCCESS;
    }

    /**
     * Resolve dependencies for the command.
     *
     * @throws RuntimeException
     */
    private function resolveDependencies(): void
    {
        $this->gitService = new GitService();

        $token = getenv('OPENAI_API_KEY');

        if ($token === false) {
            throw new RuntimeException('Environment variable OPENAI_API_KEY is not set.');
        }

        $this->aiService = new AiService($token);
    }
}
