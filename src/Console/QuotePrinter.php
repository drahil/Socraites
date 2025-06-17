<?php

declare(strict_types=1);

namespace drahil\Socraites\Console;

use drahil\Socraites\Resources\SocratesQuotes;
use Symfony\Component\Console\Output\OutputInterface;

class QuotePrinter
{
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function printQuote(): void
    {
        $this->output->writeln('');
        $this->output->writeln("<info>Analyzing your code...</info>");
        $this->output->writeln('');
        $this->output->writeln("<comment>\"" . SocratesQuotes::getRandomQuote() . "\"</comment>");
        $this->output->writeln("<comment>- Socrates</comment>");
        $this->output->writeln('');
    }
}
