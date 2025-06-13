<?php

if (! function_exists('snake_case_to_sentence_style')) {
    /**
     * Convert a snake_case string to a human-readable sentence style.
     *
     * @param string $input The snake_case string to convert.
     * @return string The converted sentence style string.
     */
    function snake_case_to_sentence_style(string $input): string
    {
        return ucfirst(str_replace('_', ' ', $input));
    }
}
