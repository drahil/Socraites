<?php

declare(strict_types=1);

namespace drahil\Socraites\Console;

use drahil\Socraites\Console\Commands\CodeReviewCommand;
use drahil\Socraites\Console\Commands\SetupCommand;
use drahil\Socraites\Console\Commands\VectorizeCommand;
use drahil\Socraites\Console\Formatters\OutputFormatter;
use drahil\Socraites\Services\AiService;
use drahil\Socraites\Services\ChangedFilesService;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Output\ConsoleOutput;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('socraites');

        $this->add(new CodeReviewCommand(
            new ChangedFilesService(),
            new OutputFormatter([]),
            new QuotePrinter(new ConsoleOutput())
        ));

        $this->add(new SetupCommand());

        $this->add(new VectorizeCommand());
    }
}
