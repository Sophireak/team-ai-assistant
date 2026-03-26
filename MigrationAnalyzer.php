<?php

namespace YourName\TeamAIAssistant\Analyzers;

use YourName\TeamAIAssistant\Analyzers\Contracts\AnalyzerInterface;

class MigrationAnalyzer implements AnalyzerInterface
{
    public function name(): string
    {
        return 'Database migrations';
    }

    public function analyze(bool $autoFix = false): array
    {
        $issues   = [];
        $warnings = [];
        $fixed    = [];

        try {
            $output = shell_exec('php artisan migrate:status --no-ansi 2>&1');

            // Can't connect to DB — soft warning, don't block push
            if (str_contains($output ?? '', 'Could not connect')
                || str_contains($output ?? '', 'Connection refused')
                || str_contains($output ?? '', 'Access denied')) {
                $warnings[] = 'Cannot connect to database — migration check skipped. Check your .env DB credentials.';
                return compact('issues', 'warnings', 'fixed');
            }

            $pending = substr_count($output ?? '', 'Pending');

            if ($pending > 0) {
                if ($autoFix) {
                    \Artisan::call('migrate', ['--force' => true]);
                    $fixed[] = "Ran {$pending} pending migration(s)";
                } else {
                    $issues[] = "{$pending} pending migration(s) not yet run. Run: php artisan migrate";
                }
            }

            // Warn if migration table doesn't exist yet
            if (str_contains($output ?? '', 'Migration table not found')) {
                $warnings[] = 'Migrations table not found. Run: php artisan migrate to initialise it.';
            }

        } catch (\Throwable $e) {
            $warnings[] = 'Could not check migrations: ' . $e->getMessage();
        }

        return compact('issues', 'warnings', 'fixed');
    }
}
