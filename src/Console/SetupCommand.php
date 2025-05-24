<?php

namespace drahil\Socraites\Console;

use InvalidArgumentException;
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

    protected function configure(): void
    {
        $this->setDescription('Setup the AI code review tool');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $framework = $io->choice(
            'Select the framework you are using:',
            ['None', 'Laravel', 'Symfony', 'CodeIgniter', 'CakePHP', 'Zend Framework', 'Yii', 'Phalcon', 'Slim'],
            'None'
        );

        $maximumContextSize = $io->ask(
            'By default the maximum context size is 100 KB. You can change it to a value between 1 and 1024 KB.',
            100,
            function ($value) {
                if (! is_numeric($value) || $value <= 0) {
                    throw new InvalidArgumentException('The maximum context size must be a positive integer.');
                }
                return (int)$value;
            }
        );

        $verboseAnswer = $io->confirm('Do you want to enable verbose output?', false);

        $this->createSocraitesJsonFile($framework, $maximumContextSize, $verboseAnswer);

        $io->writeln('<info>Socraites setup completed successfully!</info>');
        $io->writeln('<comment>Configuration saved in .socraites.json</comment>');

        return Command::SUCCESS;
    }

    /**
     * Create the .socraites.json file with the provided configuration.
     *
     * @param $framework
     * @param $maximumContextSize
     * @param $verboseAnswer
     * @return void
     */
    private function createSocraitesJsonFile($framework, $maximumContextSize, $verboseAnswer): void
    {
        $socraitesJson = [
            'framework' => $framework,
            'maximum_context_size' => $maximumContextSize * 1024,
            'verbose' => $verboseAnswer,
        ];

        file_put_contents('.socraites.json', json_encode($socraitesJson, JSON_PRETTY_PRINT));
    }
}
