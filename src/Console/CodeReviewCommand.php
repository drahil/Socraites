<?php

namespace drahil\Socraites\Console;

use Dotenv\Dotenv;
use drahil\Socraites\Console\Formatters\OutputFormatter;
use drahil\Socraites\Services\AiService;
use drahil\Socraites\Services\ContextBuilder;
use drahil\Socraites\Services\GitService;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CodeReviewCommand extends Command
{
    protected GitService $gitService;
    protected AiService $aiService;
    protected array $context;
    protected ContextBuilder $contextBuilder;
    protected OutputFormatter $formatter;
    protected QuotePrinter $quotePrinter;

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
     * @throws GuzzleException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $changedCode = $this->gitService->getGitDiff();
        $changedFiles = $this->gitService->getChangedFiles();

        $this->quotePrinter->printQuote();

        $this->contextBuilder = new ContextBuilder($changedFiles);
        $context = $this->contextBuilder->buildContext();

        $codeReview = $this->aiService->getCodeReview($changedCode, $context);

        $this->formatter->setReview(json_decode($codeReview, true));
        $this->formatter->print();

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
        $this->formatter = new OutputFormatter([]);
        $this->quotePrinter = new QuotePrinter(new ConsoleOutput());

        if (! getenv('OPENAI_API_KEY')) {
            $dotenv = Dotenv::createImmutable(getcwd());
            $dotenv->load();
        }

        $token = $_ENV['OPENAI_API_KEY'] ?? null;

        if (! $token) {
            throw new \RuntimeException('OPENAI_API_KEY is not set.');
        }

        $this->aiService = new AiService($token);
    }
}
