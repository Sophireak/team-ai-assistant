<?php

namespace YourName\TeamAIAssistant\Analyzers\Contracts;

interface AnalyzerInterface
{
    /**
     * Run the analyzer and return results.
     *
     * Returns an array with:
     *   'issues'   => string[]   — blockers that must be fixed
     *   'warnings' => string[]   — non-blocking notices
     *   'fixed'    => string[]   — things auto-fixed (when $autoFix = true)
     */
    public function analyze(bool $autoFix = false): array;

    /**
     * Human-readable name shown in terminal output.
     */
    public function name(): string;
}
