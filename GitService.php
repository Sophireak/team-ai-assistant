<?php

namespace YourName\TeamAIAssistant\Services;

class GitService
{
    /**
     * Get the current branch name.
     */
    public function currentBranch(): string
    {
        return trim(shell_exec('git rev-parse --abbrev-ref HEAD 2>&1') ?? '');
    }

    /**
     * Get short git status (list of changed files).
     */
    public function status(): array
    {
        $output = trim(shell_exec('git status --short 2>&1') ?? '');
        if (empty($output)) {
            return [];
        }
        return array_filter(explode("\n", $output));
    }

    /**
     * Get the diff of staged changes (what's about to be committed).
     */
    public function stagedDiff(): string
    {
        return shell_exec('git diff --cached 2>&1') ?? '';
    }

    /**
     * Get the diff of the last N commits vs origin.
     */
    public function unpushedDiff(int $commits = 1): string
    {
        return shell_exec("git diff HEAD~{$commits}..HEAD 2>&1") ?? '';
    }

    /**
     * Get list of PHP files changed in staged or recent commits.
     */
    public function changedPhpFiles(bool $staged = true): array
    {
        $cmd    = $staged ? 'git diff --cached --name-only' : 'git diff HEAD~1 --name-only';
        $output = shell_exec("{$cmd} 2>&1") ?? '';
        $files  = array_filter(explode("\n", trim($output)));

        return array_values(array_filter($files, fn($f) => str_ends_with($f, '.php')));
    }

    /**
     * Check if we're inside a git repository.
     */
    public function isRepo(): bool
    {
        $output = shell_exec('git rev-parse --is-inside-work-tree 2>&1');
        return trim($output ?? '') === 'true';
    }

    /**
     * Check if the current branch is a protected branch.
     */
    public function isProtectedBranch(): bool
    {
        $protected = config('team-ai-assistant.protected_branches', ['main', 'master']);
        return in_array($this->currentBranch(), $protected, true);
    }
}
