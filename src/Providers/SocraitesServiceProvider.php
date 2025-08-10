<?php

namespace drahil\Socraites\Providers;

use drahil\Socraites\Console\Commands\CodeReviewCommand;
use drahil\Socraites\Console\Commands\SetupCommand;
use drahil\Socraites\Console\Commands\SetupGitHookCommand;
use drahil\Socraites\Console\Commands\VectorizeCommand;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class SocraitesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/socraites.php' => config_path('socraites.php'),
        ], 'config');

        $this->setupPostInstall();
    }

    /**
     * @throws BindingResolutionException
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/socraites.php', 'socraites');

        $this->publishes([
            __DIR__
            . '/../../database/migrations/create_code_chunks_table.php.stub' => $this->getMigrationFileName('create_code_chunks_table.php'),
        ], 'socraites-migrations');

        $this->commands([
            CodeReviewCommand::class,
            SetupCommand::class,
            VectorizeCommand::class,
            SetupGitHookCommand::class,
        ]);
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     * @throws BindingResolutionException
     */
    protected function getMigrationFileName($migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $migrationFileName) {
                return $filesystem->glob($path . '*_' . $migrationFileName);
            })
            ->push($this->app->databasePath() . "/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }

    private function setupPostInstall(): void
    {
        // Only run during composer install/update, not on every request
        if (! $this->isComposerInstall()) {
            return;
        }

        // Ask user if they want to setup the git hook
        if (is_dir(base_path('.git'))) {
            echo "\n🤖 Socraites: Would you like to setup automatic vectorization on git pulls? (y/n): ";
            $handle = fopen("php://stdin", "r");
            $response = trim(fgets($handle));
            fclose($handle);

            if (strtolower($response) === 'y' || strtolower($response) === 'yes') {
                $this->app->make('Illuminate\Contracts\Console\Kernel')
                    ->call('socraites:setup-git-hook');
                echo "✅ Git hook installed!\n";
            }
        }
    }

    private function isComposerInstall(): bool
    {
        return defined('COMPOSER_BINARY') ||
            isset($_SERVER['COMPOSER_BINARY']) ||
            (isset($_SERVER['argv']) && in_array('install', $_SERVER['argv'])) ||
            (isset($_SERVER['argv']) && in_array('update', $_SERVER['argv']));
    }
}
