<?php

declare(strict_types=1);

namespace drahil\Socraites\Console;

use drahil\Socraites\Resources\SocratesQuotes;
use Symfony\Component\Console\Output\OutputInterface;

class QuotePrinter
{

    public function __construct()
    {
    }

    public function printQuote(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln("<info>Analyzing your code...</info>");
        $output->writeln('');
        $output->writeln("<comment>\"" . SocratesQuotes::getRandomQuote() . "\"</comment>");
        $output->writeln("<comment>- Socrates</comment>");
        $output->writeln('');
    }
}
