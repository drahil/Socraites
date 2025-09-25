<?php

declare(strict_types=1);

namespace drahil\Socraites\Services\Tasks;

use drahil\Socraites\Services\ContextState;

interface ContextTaskInterface
{
    public function execute(ContextState $state): void;
}