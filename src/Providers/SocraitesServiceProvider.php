<?php

namespace drahil\Socraites\Providers;

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
}
