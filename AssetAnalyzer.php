<?php

namespace YourName\TeamAIAssistant\Analyzers;

use YourName\TeamAIAssistant\Analyzers\Contracts\AnalyzerInterface;

class AssetAnalyzer implements AnalyzerInterface
{
    public function name(): string
    {
        return 'NPM & frontend assets';
    }

    public function analyze(bool $autoFix = false): array
    {
        $issues   = [];
        $warnings = [];
        $fixed    = [];

        // No package.json — not a frontend project, skip silently
        if (! file_exists(base_path('package.json'))) {
            return compact('issues', 'warnings', 'fixed');
        }

        // ── node_modules ─────────────────────────────────────────────────────
        if (! is_dir(base_path('node_modules'))) {
            if ($autoFix) {
                shell_exec('npm install 2>&1');
                $fixed[] = 'npm install completed';
            } else {
                $issues[] = 'node_modules not found. Run: npm install';
            }
        }

        // ── public/build ─────────────────────────────────────────────────────
        if (! is_dir(public_path('build'))) {
            if ($autoFix) {
                shell_exec('npm run build 2>&1');
                $fixed[] = 'Assets compiled with npm run build';
            } else {
                $issues[] = 'public/build not found — assets not compiled. Run: npm run build';
            }
        } else {
            // Stale check: any source file newer than build output?
            if ($this->assetsAreStale()) {
                if ($autoFix) {
                    shell_exec('npm run build 2>&1');
                    $fixed[] = 'Stale assets rebuilt';
                } else {
                    $warnings[] = 'Frontend source files changed since last build. Run: npm run build';
                }
            }
        }

        return compact('issues', 'warnings', 'fixed');
    }

    private function assetsAreStale(): bool
    {
        $buildTime  = filemtime(public_path('build'));
        $sourceDirs = ['resources/js', 'resources/css', 'resources/vue', 'resources/ts'];

        foreach ($sourceDirs as $dir) {
            $fullPath = base_path($dir);
            if (! is_dir($fullPath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullPath, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getMTime() > $buildTime) {
                    return true;
                }
            }
        }

        return false;
    }
}
