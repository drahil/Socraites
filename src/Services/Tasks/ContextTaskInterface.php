<?php

namespace drahil\Socraites\Services\Tasks;

use drahil\Socraites\Services\ContextState;

interface ContextTaskInterface
{
    public function execute(ContextState $state): void;
}