<?php

namespace drahil\Socraites\Providers;

use Illuminate\Support\ServiceProvider;

class SocraitesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/socraites.php', 'socraites');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/socraites.php' => config_path('socraites.php'),
        ], 'config');
    }
}
