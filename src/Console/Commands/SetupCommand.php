<?php

declare(strict_types=1);

namespace drahil\Socraites\Console\Commands;

use drahil\Socraites\Services\SocraitesConfigBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetupCommand extends Command
{
    public function __construct()
    {
        parent::__construct('setup');
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Setup the AI code review tool');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $configBuilder = new SocraitesConfigBuilder($io);
        $configBuilder->build();

        $io->writeln('<info>Socraites setup completed successfully!</info>');
        $io->writeln('<comment>Configuration saved in .socraites.json</comment>');

        return Command::SUCCESS;
    }
}
