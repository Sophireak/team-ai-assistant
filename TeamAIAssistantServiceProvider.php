<?php

namespace YourName\TeamAIAssistant;

use Illuminate\Support\ServiceProvider;
use YourName\TeamAIAssistant\Commands\CheckCommand;
use YourName\TeamAIAssistant\Commands\ReviewCommand;
use YourName\TeamAIAssistant\Commands\SetupCommand;
use YourName\TeamAIAssistant\Services\AIService;
use YourName\TeamAIAssistant\Services\Contracts\AIServiceInterface;
use YourName\TeamAIAssistant\Services\GitService;

class TeamAIAssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/team-ai-assistant.php',
            'team-ai-assistant'
        );

        // Bind interfaces to implementations (swap these to use a different AI provider)
        $this->app->bind(AIServiceInterface::class, AIService::class);

        // Bind services as singletons
        $this->app->singleton(AIService::class);
        $this->app->singleton(GitService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishables();
        }
    }

    private function registerCommands(): void
    {
        $this->commands([
            CheckCommand::class,
            ReviewCommand::class,
            SetupCommand::class,
        ]);
    }

    private function registerPublishables(): void
    {
        // Config
        $this->publishes([
            __DIR__ . '/../config/team-ai-assistant.php' => config_path('team-ai-assistant.php'),
        ], 'team-ai-assistant-config');

        // Git hook stub
        $this->publishes([
            __DIR__ . '/../stubs/pre-push' => base_path('.githooks/pre-push'),
        ], 'team-ai-assistant-hooks');
    }
}
