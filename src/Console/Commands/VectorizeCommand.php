<?php

namespace drahil\Socraites\Console\Commands;

use drahil\Socraites\Parsers\FileChunksParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VectorizeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('vectorize');
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Vectorize whole codebase');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // WIP
        $files = glob(base_path('app/**/*.php'));

        if (empty($files)) {
            $output->writeln('<error>No PHP files found in the app directory.</error>');
            return Command::FAILURE;
        }

        $fileParser = new FileChunksParser();

        $output->writeln('<info>Vectorizing files...</info>');
        foreach ($files as $file) {
            $output->writeln("<info>Processing file: {$file}</info>");
            $fileParser->parse($file);
        }

        $output->writeln('<info>Vectorization completed successfully.</info>');
        return Command::SUCCESS;
    }
}
