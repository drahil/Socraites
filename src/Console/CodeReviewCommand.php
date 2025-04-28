<?php

namespace drahil\Socraites\Console;

use drahil\Socraites\Services\AiService;
use drahil\Socraites\Services\GitService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CodeReviewCommand extends Command
{
    protected GitService $gitService;
    protected AiService $aiService;
    private string $name;


    public function __construct()
    {
        $this->resolveDependencies();
        parent::__construct('code-review');
    }

    protected function configure(): void
    {
        $this->setDescription('Perform an AI code review');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $changedFiles = $this->gitService->getGitDiff();

        $codeReview = $this->aiService->getCodeReview($changedFiles);

        $output->writeln($codeReview);

        return Command::SUCCESS;
    }

    private function resolveDependencies(): void
    {
        $this->gitService = new GitService();

        $token = getenv('OPENAI_API_KEY');

        if ($token === false) {
            throw new \RuntimeException('Environment variable OPENAI_API_KEY is not set.');
        }

        $this->aiService = new AiService($token);
    }
}
