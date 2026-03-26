<?php

namespace YourName\TeamAIAssistant\Analyzers;

use YourName\TeamAIAssistant\Analyzers\Contracts\AnalyzerInterface;

class ComposerAnalyzer implements AnalyzerInterface
{
    public function name(): string
    {
        return 'Composer dependencies';
    }

    public function analyze(bool $autoFix = false): array
    {
        $issues   = [];
        $warnings = [];
        $fixed    = [];

        $vendor       = base_path('vendor');
        $composerJson = base_path('composer.json');
        $composerLock = base_path('composer.lock');

        // ── vendor/ exists ───────────────────────────────────────────────────
        if (! is_dir($vendor)) {
            if ($autoFix) {
                shell_exec('composer install 2>&1');
                $fixed[] = 'composer install completed';
            } else {
                $issues[] = 'vendor/ directory missing. Run: composer install';
            }
            return compact('issues', 'warnings', 'fixed');
        }

        // ── composer.json newer than composer.lock ───────────────────────────
        if (file_exists($composerJson) && file_exists($composerLock)) {
            if (filemtime($composerJson) > filemtime($composerLock)) {
                $warnings[] = 'composer.json is newer than composer.lock — did you add a package without running composer update?';
            }
        }

        // ── composer.lock not committed ──────────────────────────────────────
        if (! file_exists($composerLock)) {
            $warnings[] = 'composer.lock is missing — it should be committed to version control.';
        }

        return compact('issues', 'warnings', 'fixed');
    }
}
