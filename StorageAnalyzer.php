<?php

namespace YourName\TeamAIAssistant\Analyzers;

use YourName\TeamAIAssistant\Analyzers\Contracts\AnalyzerInterface;

class StorageAnalyzer implements AnalyzerInterface
{
    public function name(): string
    {
        return 'Storage & permissions';
    }

    public function analyze(bool $autoFix = false): array
    {
        $issues   = [];
        $warnings = [];
        $fixed    = [];

        // ── public/storage symlink ───────────────────────────────────────────
        if (! file_exists(public_path('storage'))) {
            if ($autoFix) {
                \Artisan::call('storage:link');
                $fixed[] = 'Storage symlink created';
            } else {
                $issues[] = 'public/storage symlink missing. Run: php artisan storage:link';
            }
        }

        // ── Writable directories ─────────────────────────────────────────────
        $required = [
            'storage/app'              => storage_path('app'),
            'storage/app/public'       => storage_path('app/public'),
            'storage/logs'             => storage_path('logs'),
            'storage/framework/cache'  => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views'  => storage_path('framework/views'),
            'bootstrap/cache'          => base_path('bootstrap/cache'),
        ];

        foreach ($required as $label => $path) {
            // Create directory if it doesn't exist (common on fresh clones)
            if (! is_dir($path)) {
                if ($autoFix) {
                    mkdir($path, 0775, true);
                    $fixed[] = "Created missing directory: {$label}";
                } else {
                    $issues[] = "Directory missing: {$label}. Run: mkdir -p {$path}";
                }
                continue;
            }

            if (! is_writable($path)) {
                if ($autoFix) {
                    shell_exec("chmod -R 775 {$path}");
                    $fixed[] = "Fixed permissions on {$label}";
                } else {
                    $issues[] = "{$label} is not writable. Run: chmod -R 775 {$path}";
                }
            }
        }

        return compact('issues', 'warnings', 'fixed');
    }
}
