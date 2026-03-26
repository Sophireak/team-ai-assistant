<?php

namespace YourName\TeamAIAssistant\Commands;

use Illuminate\Console\Command;
use YourName\TeamAIAssistant\Services\AIService;
use YourName\TeamAIAssistant\Services\GitService;

class ReviewCommand extends Command
{
    protected $signature = 'ai:review
        {file?          : Specific file to review (optional)}
        {--staged       : Review staged git changes}
        {--last         : Review changes in the last commit}';

    protected $description = 'AI code review of staged changes or a specific file';

    public function __construct(
        private readonly AIService  $ai,
        private readonly GitService $git,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->ai->isAvailable()) {
            $this->error('ANTHROPIC_API_KEY is not set. Add it to your .env to use AI review.');
            return 1;
        }

        [$code, $label] = $this->getCodeToReview();

        if (empty(trim($code))) {
            $this->warn('Nothing to review. Stage some changes or pass a file path.');
            return 0;
        }

        $this->newLine();
        $this->line("<fg=cyan;options=bold>Reviewing: {$label}</>");
        $this->line('<fg=cyan>Asking AI for feedback...</>');
        $this->newLine();

        $context  = $this->buildContext();
        $feedback = $this->ai->reviewCode($code, $context);

        if (empty($feedback)) {
            $this->warn('No response from AI.');
            return 1;
        }

        $this->line('<fg=cyan;options=bold>┌─ AI Code Review ───────────────────────────────┐</>');
        $this->newLine();
        foreach (explode("\n", $feedback) as $line) {
            $this->line('  ' . $line);
        }
        $this->newLine();
        $this->line('<fg=cyan;options=bold>└────────────────────────────────────────────────┘</>');
        $this->newLine();

        return 0;
    }

    private function getCodeToReview(): array
    {
        // Specific file passed
        if ($file = $this->argument('file')) {
            $path = base_path($file);
            if (! file_exists($path)) {
                $this->error("File not found: {$file}");
                exit(1);
            }
            return [file_get_contents($path), $file];
        }

        // Staged changes
        if ($this->option('staged') || (! $this->option('last'))) {
            $diff = $this->git->stagedDiff();
            if (! empty(trim($diff))) {
                return [$diff, 'staged changes'];
            }
        }

        // Last commit diff
        if ($this->option('last')) {
            return [$this->git->unpushedDiff(1), 'last commit'];
        }

        // Fallback: list changed PHP files and let user pick
        $files = $this->git->changedPhpFiles(staged: true);
        if (empty($files)) {
            $files = $this->git->changedPhpFiles(staged: false);
        }

        if (empty($files)) {
            return ['', ''];
        }

        $choice = $this->choice('Which file do you want reviewed?', $files);
        $path   = base_path($choice);

        return [file_get_contents($path), $choice];
    }

    private function buildContext(): string
    {
        $parts = [];

        $parts[] = 'Stack: Laravel 11, PHP 8.3, Tailwind CSS, MySQL';

        if (file_exists(base_path('composer.json'))) {
            $json = json_decode(file_get_contents(base_path('composer.json')), true);
            $name = $json['name'] ?? null;
            if ($name) {
                $parts[] = "Package: {$name}";
            }
        }

        $branch = $this->git->currentBranch();
        if ($branch) {
            $parts[] = "Branch: {$branch}";
        }

        return implode("\n", $parts);
    }
}
