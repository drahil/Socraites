<?php

declare(strict_types=1);

namespace drahil\Socraites\Console;

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

        $apiKey = socraites_config('openai_api_key');

        if (! $apiKey) {
            echo "Missing OpenAI API key.\n";
            exit(1);
        }

        $this->add(new CodeReviewCommand(
            new ChangedFilesService(),
            new AiService($apiKey),
            new OutputFormatter([]),
            new QuotePrinter(new ConsoleOutput())
        ));

        $this->add(new SetupCommand());
    }
}
