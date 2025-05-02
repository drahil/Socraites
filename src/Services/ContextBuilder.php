<?php

namespace drahil\Socraites\Services;

use drahil\Socraites\Parsers\FileParser;
use drahil\Socraites\Services\Tasks\AddChangedFilesToContextTask;
use drahil\Socraites\Services\Tasks\CreateContextTask;
use drahil\Socraites\Services\Tasks\ProcessFilesTask;
use Exception;

class ContextBuilder
{
    private ContextState $state;
    private array $tasks;

    public function __construct(array $changedFiles)
    {
        $this->state = new ContextState(changedFiles: $changedFiles);

        $this->tasks = [
            new AddChangedFilesToContextTask(),
            new ProcessFilesTask(new FileParser()),
            new CreateContextTask(new ClassMapService())
        ];
    }

    /**
     * Build the context by executing the tasks in order.
     *
     * @return array The built context.
     */
    public function buildContext(): array
    {
        foreach ($this->tasks as $task) {
            try {
                $task->execute($this->state);
            } catch (Exception $e) {
                echo "Error executing task: " . $e->getMessage() . "\n";
            }
        }

        return $this->state->context;
    }
}
