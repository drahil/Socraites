<?php

if (! function_exists('socraites_config')) {
    function socraites_config(string $key, $default = null)
    {
        static $userConfig = null;

        if ($userConfig === null) {
            $userConfig = [];

            $path = getcwd() . DIRECTORY_SEPARATOR . '.socraites.json';

            if (file_exists($path)) {
                $json = file_get_contents($path);
                $userConfig = json_decode($json, true) ?? [];
            }
        }

        // 1. Check .socraites.json
        if (array_key_exists($key, $userConfig)) {
            return $userConfig[$key];
        }

        // 2. Laravel-style config fallback
        try {
            if (function_exists('config') && function_exists('app') && app()->bound('config')) {
                return config("socraites.$key", $default);
            }
        } catch (\Throwable $e) {
            // Laravel not available
        }

        // 3. Check environment variable
        $envKey = strtoupper(str_replace('.', '_', "socraites.$key"));
        $envVal = getenv($envKey);

        return $envVal !== false ? $envVal : $default;
    }
}
