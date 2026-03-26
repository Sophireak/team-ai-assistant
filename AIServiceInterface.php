<?php

namespace YourName\TeamAIAssistant\Services\Contracts;

interface AIServiceInterface
{
    /**
     * Explain a list of issues in plain language with fix instructions.
     *
     * @param  string[] $issues
     */
    public function explainIssues(array $issues): string;

    /**
     * Review a code diff or file content and return suggestions.
     */
    public function reviewCode(string $code, string $context = ''): string;

    /**
     * Check if the AI service is configured and reachable.
     */
    public function isAvailable(): bool;
}
