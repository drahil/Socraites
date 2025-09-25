<?php

declare(strict_types=1);

namespace drahil\Socraites\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class ChangedFilesService
{
    /**
     * Get the list of changed files in the git repository and filter them based on configuration.
     *
     * @return array List of changed files.
     * @throws RuntimeException If the git command fails.
     */
    public function getChangedFiles(): array
    {
        $files = $this->getChangedFilesFromGit();

        return $this->filterFiles($files);
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

    /**
     * Get the list of changed files from the git repository.
     *
     * @return array List of changed files.
     * @throws RuntimeException If the git command fails.
     */
    private function getChangedFilesFromGit(): array
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

            if ($statusCode !== '??') {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Filter the list of files based on the configuration.
     *
     * @param array $files List of files to filter.
     * @return array Filtered list of files.
     */
    private function filterFiles(array $files): array
    {
        if (socraites_config('ignore_patterns')) {
            $files = array_filter($files, function ($file) {
                foreach (socraites_config('ignore_patterns') as $pattern) {
                    if (fnmatch($pattern, $file)) {
                        return false;
                    }
                }
                return true;
            });
        }

        if (socraites_config('extensions')) {
            $files = array_filter($files, function ($file) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                return in_array($extension, socraites_config('extensions'));
            });
        }

        return $files;
    }
}
