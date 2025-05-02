<?php

namespace drahil\Socraites\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class GitService
{
    /**
     * Get the list of changed files in the git repository.
     *
     * @return array List of changed files.
     * @throws RuntimeException If the git command fails.
     */
    public function getChangedFiles(): array
    {
        $command = ['git', 'status', '--porcelain'];

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to get git status: ' . $process->getErrorOutput());
        }

        $output = $process->getOutput();

        $files = [];
        foreach (explode("\n", $output) as $line) {
            if (empty($line)) {
                continue;
            }

            // Parse the git status porcelain format
            // Format is: XY PATH or XY PATH1 -> PATH2 (for renames)
            $statusCode = substr($line, 0, 2);
            $path = trim(substr($line, 3));

            // Handle renamed files
            if (str_contains($path, ' -> ')) {
                list(, $path) = explode(' -> ', $path);
            }

            // TODO decide how to handle deleted files
            if ($statusCode[0] === 'D' || $statusCode[1] === 'D') {
            }

            if ($statusCode !== '??' && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Get the git diff of the staged files.
     *
     * @return string The git diff output.
     * @throws RuntimeException If the git command fails.
     */
    public function getGitDiff(): string
    {
        $command = ['git', 'diff', '--staged'];

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to get git diff: ' . $process->getErrorOutput());
        }

        return $process->getOutput();
    }
}
