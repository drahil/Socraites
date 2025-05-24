<?php

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
            'framework' => $this->askFramework(),
            'maximum_context_size' => $this->askContextSize(),
            'verbose_answer' => $this->askVerbose(),
            'ignore_patterns' => $this->askIgnorePatterns(),
            'extensions' => $this->askExtensions(),
        ];

        file_put_contents('.socraites.json', json_encode($socraitesJson, JSON_PRETTY_PRINT));
    }

    /**
     * Ask the user for the framework they are using.
     *
     * @return string The selected framework.
     */
    private function askFramework(): string
    {
        return $this->io->choice(
            'Select the framework you are using:',
            ['None', 'Laravel', 'Symfony', 'CodeIgniter', 'CakePHP', 'Zend Framework', 'Yii', 'Phalcon', 'Slim'],
            'None'
        );
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
            100,
            function ($value) {
                if (! is_numeric($value) || $value <= 0) {
                    throw new InvalidArgumentException('The maximum context size must be a positive integer.');
                }
                return (int) $value * 1024;
            }
        );
    }

    /**
     * Ask the user if they want verbose output.
     *
     * @return bool True if verbose output is enabled, false otherwise.
     */
    private function askVerbose(): bool
    {
        return $this->io->confirm('Do you want to enable verbose output?', false);
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
}
