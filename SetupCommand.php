<?php

namespace YourName\TeamAIAssistant\Commands;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $signature   = 'ai:setup';
    protected $description = 'Interactive first-time setup wizard for Team AI Assistant';

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>┌─────────────────────────────────────────┐</>');
        $this->line('<fg=cyan;options=bold>│     Team AI Assistant — Setup Wizard    │</>');
        $this->line('<fg=cyan;options=bold>└─────────────────────────────────────────┘</>');
        $this->newLine();
        $this->line('This wizard will get you set up in under a minute.');
        $this->newLine();

        $this->stepPublishConfig();
        $this->stepApiKey();
        $this->stepGitHook();
        $this->stepTestRun();

        $this->newLine();
        $this->line('<fg=green;options=bold>Setup complete!</>');
        $this->newLine();
        $this->line('From now on, <fg=cyan>git push</> will automatically run all checks.');
        $this->line('You can also run manually: <fg=cyan>php artisan ai:check --fix</>');
        $this->line('For code review:           <fg=cyan>php artisan ai:review</>');
        $this->newLine();

        return 0;
    }

    // ── Step 1: Publish config ────────────────────────────────────────────────
    private function stepPublishConfig(): void
    {
        $this->line('<options=bold>Step 1 — Publishing config</>');

        $configPath = config_path('team-ai-assistant.php');

        if (file_exists($configPath)) {
            $this->line('  <fg=green>✔</> Config already published at config/team-ai-assistant.php');
        } else {
            $this->call('vendor:publish', [
                '--tag'    => 'team-ai-assistant-config',
                '--force'  => false,
            ]);
            $this->line('  <fg=green>✔</> Config published to config/team-ai-assistant.php');
        }

        $this->newLine();
    }

    // ── Step 2: API key ───────────────────────────────────────────────────────
    private function stepApiKey(): void
    {
        $this->line('<options=bold>Step 2 — Anthropic API key</>');

        $existing = env('ANTHROPIC_API_KEY', '');

        if (! empty($existing)) {
            $this->line('  <fg=green>✔</> ANTHROPIC_API_KEY is already set in .env');
            $this->newLine();
            return;
        }

        $this->line('  <fg=yellow>⚠</> ANTHROPIC_API_KEY not found in .env');
        $this->line('  AI explanations and code review need this key.');
        $this->line('  Get yours at: <fg=cyan>https://console.anthropic.com/</>');
        $this->newLine();

        if ($this->confirm('  Do you want to add it now?', true)) {
            $key = $this->secret('  Paste your API key (input hidden)');

            if (! empty($key)) {
                $this->appendToEnv('ANTHROPIC_API_KEY', $key);
                $this->line('  <fg=green>✔</> Added ANTHROPIC_API_KEY to .env');
            } else {
                $this->warn('  Skipped — you can add it manually to .env later.');
            }
        } else {
            $this->line('  Skipped — checks will still run without AI explanations.');
        }

        $this->newLine();
    }

    // ── Step 3: Git hook ──────────────────────────────────────────────────────
    private function stepGitHook(): void
    {
        $this->line('<options=bold>Step 3 — Activate the git hook</>');

        // Check if already activated
        $hooksPath = trim(shell_exec('git config core.hooksPath 2>&1') ?? '');

        if ($hooksPath === '.githooks') {
            $this->line('  <fg=green>✔</> Git hook already active (.githooks)');
            $this->newLine();
            return;
        }

        // Check if .githooks/pre-push exists in repo
        if (! file_exists(base_path('.githooks/pre-push'))) {
            $this->line('  <fg=yellow>⚠</> .githooks/pre-push not found in project root.');
            $this->line('  Ask your team lead to commit the .githooks/ folder first.');
            $this->newLine();
            return;
        }

        $this->line('  Run this one command to activate automatic pre-push checks:');
        $this->newLine();
        $this->line('    <fg=cyan>git config core.hooksPath .githooks</>');
        $this->newLine();

        if ($this->confirm('  Run it now?', true)) {
            shell_exec('git config core.hooksPath .githooks');
            $this->line('  <fg=green>✔</> Git hook activated');
        } else {
            $this->line('  Run it manually when ready.');
        }

        $this->newLine();
    }

    // ── Step 4: Test run ──────────────────────────────────────────────────────
    private function stepTestRun(): void
    {
        $this->line('<options=bold>Step 4 — Test run</>');

        if ($this->confirm('  Run ai:check now to see how everything looks?', true)) {
            $this->newLine();
            $this->call('ai:check', ['--skip-ai' => true]);
        } else {
            $this->line('  Skipped — run php artisan ai:check whenever you\'re ready.');
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function appendToEnv(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $line    = "\n{$key}={$value}\n";
        file_put_contents($envPath, $line, FILE_APPEND);
    }
}
