<?php

if (! function_exists('socraites_config')) {
    function socraites_config(string $key, $default = null)
    {
        try {
            if (function_exists('config') && app()->bound('config')) {
                return config("socraites.$key", $default);
            }
        } catch (\Throwable $e) {
            // Ignore Laravel-specific failures
        }

        // Fallback to environment variable
        $envKey = strtoupper(str_replace('.', '_', "socraites.$key"));

        return getenv($envKey) !== false ? getenv($envKey) : $default;
    }
}
