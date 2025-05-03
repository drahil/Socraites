<?php

if (! function_exists('socraites_config')) {
    function socraites_config(string $key, $default = null)
    {
        // If Laravel's config() function exists
        if (function_exists('config')) {
            return config("socraites.$key", $default);
        }

        // Convert Laravel-style key (e.g. socraites.scores.import)
        // to env-style key (e.g. SOCRAITES_SCORES_IMPORT)
        $envKey = strtoupper(str_replace('.', '_', "socraites.$key"));

        return getenv($envKey) !== false ? getenv($envKey) : $default;
    }
}
