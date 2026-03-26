<?php

namespace YourName\TeamAIAssistant\Analyzers;

use YourName\TeamAIAssistant\Analyzers\Contracts\AnalyzerInterface;

class EnvAnalyzer implements AnalyzerInterface
{
    public function name(): string
    {
        return '.env configuration';
    }

    public function analyze(bool $autoFix = false): array
    {
        $issues   = [];
        $warnings = [];
        $fixed    = [];

        $envPath     = base_path('.env');
        $examplePath = base_path('.env.example');

        // ── .env exists ──────────────────────────────────────────────────────
        if (! file_exists($envPath)) {
            if ($autoFix && file_exists($examplePath)) {
                copy($examplePath, $envPath);
                $fixed[]    = '.env created from .env.example';
                $warnings[] = '.env was just created — fill in DB_PASSWORD and other secrets before pushing.';
            } else {
                $issues[] = '.env file is missing. Run: cp .env.example .env';
            }
            return compact('issues', 'warnings', 'fixed');
        }

        // ── APP_KEY ──────────────────────────────────────────────────────────
        if (empty(env('APP_KEY'))) {
            if ($autoFix) {
                \Artisan::call('key:generate');
                $fixed[] = 'APP_KEY generated';
            } else {
                $issues[] = 'APP_KEY is not set. Run: php artisan key:generate';
            }
        }

        // ── Keys missing vs .env.example ────────────────────────────────────
        if (file_exists($examplePath)) {
            $missing = array_diff(
                $this->parseKeys($examplePath),
                $this->parseKeys($envPath)
            );
            if (! empty($missing)) {
                $issues[] = 'Missing keys from .env.example: ' . implode(', ', $missing)
                    . '. Copy them from .env.example and fill in the values.';
            }
        }

        // ── Empty critical DB keys ───────────────────────────────────────────
        $emptyDb = array_filter(['DB_HOST', 'DB_DATABASE', 'DB_USERNAME'], fn($k) => empty(env($k)));
        if (! empty($emptyDb)) {
            $issues[] = 'Empty DB keys in .env: ' . implode(', ', $emptyDb);
        }

        // ── APP_DEBUG on production ──────────────────────────────────────────
        if (env('APP_ENV') === 'production' && env('APP_DEBUG', false)) {
            $issues[] = 'APP_DEBUG=true in production — this leaks sensitive error details.';
        }

        // ── Stale config cache ───────────────────────────────────────────────
        $cached = base_path('bootstrap/cache/config.php');
        if (file_exists($cached) && filemtime($envPath) > filemtime($cached)) {
            $warnings[] = 'Config cache is stale after .env changes. Run: php artisan config:cache';
        }

        return compact('issues', 'warnings', 'fixed');
    }

    private function parseKeys(string $path): array
    {
        $keys = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            $keys[] = explode('=', $line, 2)[0];
        }
        return $keys;
    }
}
