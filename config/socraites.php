<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | This is the API key used to authenticate with OpenAI's API.
    | It can be set in your .env file as OPENAI_API_KEY.
    |
    */

    'openai_api_key' => env('OPENAI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Scoring Weights
    |--------------------------------------------------------------------------
    |
    | These values determine the relative importance of different code patterns
    | during the analysis process. Adjust these to fine-tune the scoring logic.
    |
    */

    'scores' => [
        'import' => env('SOCRAITES_SCORES_IMPORT', 5),
        'extends' => env('SOCRAITES_SCORES_EXTENDS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Content Size
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum size of context that can be processed
    | by the AI service. It is set in kilobytes (KB).
    |
    | Note: The default value is set to 100KB.
    | Adjust this value based on your application's needs.
    |
    */

    'maximum_context_size' => env('SOCRAITES_MAX_CONTEXT_SIZE', 100 * 1024),
];
