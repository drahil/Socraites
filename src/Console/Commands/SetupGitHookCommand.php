<?php

namespace drahil\Socraites\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupGitHookCommand extends Command
{
    public function __construct()
    {
        parent::__construct('socraites:setup-git-hook');
    }

    protected function configure(): void
    {
        $this->setDescription('Setup git post-merge hook for automatic vectorization');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $gitDir = base_path('.git');

        if (! is_dir($gitDir)) {
            $output->writeln('<error>This is not a git repository</error>');
            return Command::FAILURE;
        }

        $hookPath = $gitDir . '/hooks/post-merge';
        $hookContent = $this->getHookContent();

        if (file_exists($hookPath)) {
            $backup = $hookPath . '.backup.' . date('Y-m-d-H-i-s');
            copy($hookPath, $backup);
            $output->writeln("<info>Existing hook backed up to: {$backup}</info>");
        }

        file_put_contents($hookPath, $hookContent);
        chmod($hookPath, 0755);

        $output->writeln('<info>Git post-merge hook installed successfully!</info>');
        $output->writeln('<info>Vectorization will now run automatically when main/master branch is updated.</info>');

        return Command::SUCCESS;
    }

    private function getHookContent(): string
    {
        $artisanPath = base_path('artisan');

        return <<<BASH
#!/bin/bash
# Socraites Auto-Vectorization Hook
# This hook runs after a successful merge (including pulls and fast-forwards)

# Get the current branch name
CURRENT_BRANCH=\$(git rev-parse --abbrev-ref HEAD)

# Only run on main/master branch
if [ "\$CURRENT_BRANCH" != "main" ] && [ "\$CURRENT_BRANCH" != "master" ]; then
    echo "Socraites: Not on main branch (\$CURRENT_BRANCH), skipping vectorization"
    exit 0
fi

echo "🤖 Socraites: Branch updated, checking for files to vectorize..."

# Get the list of changed files from the merge/fast-forward
# Use ORIG_HEAD which is set by git merge/pull to the previous HEAD
if [ -n "\$(git rev-parse --verify ORIG_HEAD 2>/dev/null)" ]; then
    CHANGED_FILES=\$(git diff-tree -r --name-only --no-commit-id ORIG_HEAD HEAD 2>/dev/null)
else
    # Fallback: compare with previous commit if ORIG_HEAD doesn't exist
    CHANGED_FILES=\$(git diff-tree -r --name-only --no-commit-id HEAD~1 HEAD 2>/dev/null)
fi

if [ -z "\$CHANGED_FILES" ]; then
    echo "Socraites: No files changed, skipping vectorization"
    exit 0
fi

echo "Socraites: Files changed in this update:"
echo "\$CHANGED_FILES"

# Filter for relevant file types and build command
FILES_TO_VECTORIZE=()
while IFS= read -r file; do
    # Check if file exists (in case it was deleted)
    if [ -f "\$file" ] && [[ "\$file" =~ \\.(php|js|ts|vue|blade\\.php)\$ ]]; then
        FILES_TO_VECTORIZE+=("--files=\$file")
    fi
done <<< "\$CHANGED_FILES"

if [ \${#FILES_TO_VECTORIZE[@]} -gt 0 ]; then
    echo "Socraites: Vectorizing \${#FILES_TO_VECTORIZE[@]} changed files..."
    php "{$artisanPath}" socraites:vectorize "\${FILES_TO_VECTORIZE[@]}"
    echo "✅ Socraites: Vectorization completed"
else
    echo "Socraites: No relevant files to vectorize (files may have been deleted or are not code files)"
fi
BASH;
    }
}
