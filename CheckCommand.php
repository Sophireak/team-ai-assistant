<?php

namespace YourName\TeamAIAssistant\Commands;

use Illuminate\Console\Command;
use YourName\TeamAIAssistant\Analyzers\Contracts\AnalyzerInterface;
use YourName\TeamAIAssistant\Services\AIService;
use YourName\TeamAIAssistant\Services\GitService;

class CheckCommand extends Command
{
    protected $signature = 'ai:check
        {--fix      : Auto-fix all fixable issues}
        {--skip-ai  : Skip AI explanations (faster, works offline)}
        {--force    : Bypass all checks and allow push}';

    protected $description = 'Run pre-push checks: env, migrations, assets, storage, and more';

    public function __construct(
        private readonly AIService  $ai,
        private readonly GitService $git,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('force')) {
            $this->warn('--force passed. Skipping all checks.');
            return 0;
        }

        $this->printBanner();

        $autoFix     = $this->option('fix');
        $allIssues   = [];
        $allWarnings = [];
        $allFixed    = [];

        // ── Run every registered analyzer ────────────────────────────────────
        $analyzers = $this->resolveAnalyzers();

        foreach ($analyzers as $analyzer) {
            $this->line('<options=bold>' . $analyzer->name() . '</>');

            $result = $analyzer->analyze($autoFix);

            foreach ($result['fixed'] ?? [] as $msg) {
                $this->line("  <fg=cyan>→</> {$msg}");
                $allFixed[] = $msg;
            }
            foreach ($result['issues'] ?? [] as $msg) {
                $this->line("  <fg=red>✘</> {$msg}");
                $allIssues[] = $msg;
            }
            foreach ($result['warnings'] ?? [] as $msg) {
                $this->line("  <fg=yellow>⚠</> {$msg}");
                $allWarnings[] = $msg;
            }

            if (empty($result['issues']) && empty($result['warnings']) && empty($result['fixed'])) {
                $this->line('  <fg=green>✔</> All good');
            }

            $this->newLine();
        }

        // ── Git status ───────────────────────────────────────────────────────
        $this->checkGit($allWarnings);

        // ── Summary ──────────────────────────────────────────────────────────
        $this->printSummary($allIssues, $allWarnings, $allFixed);

        // All clear
        if (empty($allIssues)) {
            return 0;
        }

        // Offer AI diagnosis
        if (! $this->option('skip-ai') && $this->ai->isAvailable()) {
            if ($this->confirm('  Want the AI to explain these issues?', true)) {
                $this->runAiDiagnosis($allIssues);
            }
        } elseif (! $this->ai->isAvailable() && ! $this->option('skip-ai')) {
            $this->line('  <fg=yellow>Tip: Add ANTHROPIC_API_KEY to .env for AI-powered explanations.</>');
            $this->newLine();
        }

        // Block or allow
        if ($this->confirm('  Block this push until issues are fixed?', true)) {
            $this->newLine();
            $this->line('<fg=red;options=bold>  Push blocked. Fix the issues above, then push again.</>');
            $this->line('<fg=yellow>  Emergency bypass: git push --no-verify</>');
            $this->newLine();
            return 1;
        }

        $this->warn('Pushing with unresolved issues. Be careful!');
        return 0;
    }

    // ── Resolve all analyzer classes from config ─────────────────────────────
    private function resolveAnalyzers(): array
    {
        $classes = config('team-ai-assistant.analyzers', []);

        return array_map(
            fn($class) => app($class),
            array_filter($classes, fn($class) => class_exists($class))
        );
    }

    // ── Git checks (branch + uncommitted files) ───────────────────────────────
    private function checkGit(array &$warnings): void
    {
        $this->line('<options=bold>Git status</>');

        $uncommitted = $this->git->status();
        if (! empty($uncommitted)) {
            $count = count($uncommitted);
            $this->line("  <fg=yellow>⚠</> {$count} uncommitted file(s):");
            foreach ($uncommitted as $file) {
                $this->line("      <fg=yellow>{$file}</>");
            }
            $warnings[] = "{$count} uncommitted files — were these intentional?";
        } else {
            $this->line('  <fg=green>✔</> Working tree is clean');
        }

        $branch = $this->git->currentBranch();
        if ($this->git->isProtectedBranch()) {
            $this->line("  <fg=yellow>⚠</> Pushing directly to '{$branch}'");
            $warnings[] = "Direct push to '{$branch}' is risky on a shared project. Consider a feature branch.";
        } else {
            $this->line("  <fg=green>✔</> Branch: {$branch}");
        }

        $this->newLine();
    }

    // ── Print summary ─────────────────────────────────────────────────────────
    private function printSummary(array $issues, array $warnings, array $fixed): void
    {
        $this->line('<options=bold>─── Summary ──────────────────────────────────</>');

        if (! empty($fixed)) {
            $this->line('  <fg=cyan>Auto-fixed:</>');
            foreach ($fixed as $f) {
                $this->line("    <fg=cyan>✔</> {$f}");
            }
            $this->newLine();
        }

        if (! empty($warnings)) {
            $this->line('  <fg=yellow>Warnings (non-blocking):</>');
            foreach ($warnings as $w) {
                $this->line("    <fg=yellow>⚠</> {$w}");
            }
            $this->newLine();
        }

        if (empty($issues)) {
            $this->line('  <fg=green;options=bold>All checks passed — safe to push!</>');
        } else {
            $this->line('  <fg=red;options=bold>Issues that must be fixed:</>');
            foreach ($issues as $i => $issue) {
                $this->line('    <fg=red>' . ($i + 1) . '.</> ' . $issue);
            }
        }

        $this->newLine();
    }

    // ── AI diagnosis ──────────────────────────────────────────────────────────
    private function runAiDiagnosis(array $issues): void
    {
        $this->line('<fg=cyan>  Asking AI to explain the issues...</>');

        $explanation = $this->ai->explainIssues($issues);

        if (empty($explanation)) {
            $this->warn('  No response from AI. Check ANTHROPIC_API_KEY.');
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>  ┌─ AI Explanation ──────────────────────────────┐</>');
        $this->newLine();
        foreach (explode("\n", $explanation) as $line) {
            $this->line('  ' . $line);
        }
        $this->newLine();
        $this->line('<fg=cyan;options=bold>  └───────────────────────────────────────────────┘</>');
        $this->newLine();
    }

    private function printBanner(): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>┌─────────────────────────────────────────┐</>');
        $this->line('<fg=cyan;options=bold>│     Team AI Assistant — Pre-Push Check  │</>');
        $this->line('<fg=cyan;options=bold>└─────────────────────────────────────────┘</>');
        $this->newLine();
    }
}
