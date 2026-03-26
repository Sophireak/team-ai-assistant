<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic API Key
    |--------------------------------------------------------------------------
    | Your Claude API key. Get one at https://console.anthropic.com/
    | It's safer to set this in .env as ANTHROPIC_API_KEY rather than here.
    */
    'anthropic_api_key' => env('ANTHROPIC_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | AI Model
    |--------------------------------------------------------------------------
    | The Claude model to use for explanations and code review.
    */
    'model' => env('TEAM_AI_MODEL', 'claude-sonnet-4-20250514'),

    /*
    |--------------------------------------------------------------------------
    | API Timeout
    |--------------------------------------------------------------------------
    | Seconds to wait for the AI API before giving up.
    */
    'timeout' => 30,

    /*
    |--------------------------------------------------------------------------
    | Analyzers
    |--------------------------------------------------------------------------
    | List of analyzer classes to run during ai:check.
    | Add, remove, or reorder checks here.
    | Each class must implement AnalyzerInterface.
    */
    'analyzers' => [
        \YourName\TeamAIAssistant\Analyzers\EnvAnalyzer::class,
        \YourName\TeamAIAssistant\Analyzers\MigrationAnalyzer::class,
        \YourName\TeamAIAssistant\Analyzers\AssetAnalyzer::class,
        \YourName\TeamAIAssistant\Analyzers\StorageAnalyzer::class,
        \YourName\TeamAIAssistant\Analyzers\ComposerAnalyzer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Branches
    |--------------------------------------------------------------------------
    | Branches that trigger a warning when pushed to directly.
    */
    'protected_branches' => ['main', 'master'],

    /*
    |--------------------------------------------------------------------------
    | Auto-fix on Pre-push
    |--------------------------------------------------------------------------
    | When true, the git hook runs ai:check --fix automatically.
    | Set to false if you prefer to only warn, never auto-fix.
    */
    'auto_fix' => true,

];
