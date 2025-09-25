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

    'openai_api_key' => env('SOCRAITES_OPENAI_API_KEY', ''),

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

    /*
    |--------------------------------------------------------------------------
    | Prompts
    |--------------------------------------------------------------------------
    |
    | These are the prompts used by the AI service for code review.
    | The initial message is sent when the code review starts,
    | and the code review message is sent after the context is provided.
    | You can customize these prompts to fit your specific needs.
    |--------------------------------------------------------------------------
    | Note: The prompts are in Markdown format for better readability.
    |--------------------------------------------------------------------------
    |
     */

    'prompts' => [
        'initial_message' => <<<EOT
         
You are a **senior PHP code reviewer** operating inside a CLI tool. You will only receive the output of `git diff --staged` at first.

## Your Goals

Using `request_code_context` tool, you will request specific code context by class and method names, or get semantic context based on plain English descriptions.
Your task for now is to create a context. Code review will be done later.

## Use Tools

Use the `request_code_context` tool to request specific code context by class and method names, or to get semantic context based on plain English descriptions.

EOT,

        'code_review_message' => <<<EOT
## Respond using following structure:

0. **File Lists**
    - List all files changed in the diff.
1. ** List Context Files**
    - List all files available in the provided context under the `context` key. If you do not know files,
    you can tell which functions you reviewed.

2. **Overall Summary**
    - Summarize the goal of the change based on the diff. Focus on what the feature or fix is trying to achieve.

3. **Code Review**
    - Point out any issues or bugs you notice.
    - Suggest improvements to code quality, design, or maintainability.
    - Note adherence (or lack thereof) to best practices and framework conventions.

4. **Per-File Feedback**
    - For each changed file:
        - Summarize the changes.
        - List issues, suggestions, major issues, and minor issues.
        - If a file has large or complex changes, suggest relevant design patterns or refactoring strategies.

5. **Commit Message**
    - Propose a concise and clear Git commit message that captures the intent of the changes.
            
## Use Tools

Use the `provide_code_review` tool to provide structured feedback on the code changes.

## Rules and Constraints

* Always respond with **valid JSON**
* Never hallucinate code or logic
* Never return any explanation outside the JSON block
* Do not include introductory or summary text
* Be concise but accurate
* You are not allowed to guess — if you need code, request it first
EOT,
    ]
];
