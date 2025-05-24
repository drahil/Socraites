<?php

namespace drahil\Socraites\Console;

use Symfony\Component\Console\Application as BaseApplication;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('socraites');

        $this->add(new CodeReviewCommand());
        $this->add(new SetupCommand());
    }
}
