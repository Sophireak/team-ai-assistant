<?php

namespace YourName\TeamAIAssistant\Services;

use YourName\TeamAIAssistant\Services\Contracts\AIServiceInterface;

class AIService implements AIServiceInterface
{
    private string $apiKey;
    private string $model;
    private int    $timeout;

    public function __construct()
    {
        $this->apiKey  = config('team-ai-assistant.anthropic_api_key', env('ANTHROPIC_API_KEY', ''));
        $this->model   = config('team-ai-assistant.model', 'claude-sonnet-4-20250514');
        $this->timeout = config('team-ai-assistant.timeout', 30);
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function explainIssues(array $issues): string
    {
        if (empty($issues)) {
            return '';
        }

        $numbered = implode("\n", array_map(
            fn($i, $v) => ($i + 1) . '. ' . $v,
            array_keys($issues),
            $issues
        ));

        $prompt = <<<PROMPT
A developer on a Laravel 11 + Tailwind CSS + MySQL project hit these issues before pushing to Git:

{$numbered}

For each issue:
1. Explain in plain English WHY it matters (1-2 sentences)
2. Give the exact terminal command(s) to fix it
3. Mention any gotcha to watch out for

Be friendly, direct, and practical. Use the same issue numbers. Keep it concise.
PROMPT;

        return $this->call(
            system: 'You are a friendly senior Laravel developer helping a junior teammate understand and fix issues before pushing to Git.',
            prompt: $prompt
        );
    }

    public function reviewCode(string $code, string $context = ''): string
    {
        $contextBlock = $context
            ? "Context about this project:\n{$context}\n\n"
            : '';

        $prompt = <<<PROMPT
{$contextBlock}Review this Laravel code and provide actionable feedback:

```php
{$code}
```

Focus on:
- Laravel 11 best practices and conventions
- Potential N+1 query problems
- Missing validation or security issues
- Eloquent improvements (relationships, scopes, casts)
- Tailwind/Blade improvements if applicable

Format: short bullet points grouped by severity (critical / suggestion / nitpick).
Keep it concise and practical — skip praise, just flag what can be better.
PROMPT;

        return $this->call(
            system: 'You are a senior Laravel code reviewer. Be concise, specific, and actionable. No fluff.',
            prompt: $prompt
        );
    }

    private function call(string $system, string $prompt): string
    {
        if (! $this->isAvailable()) {
            return '';
        }

        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => 1500,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: '          . $this->apiKey,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "AI request failed: {$error}";
        }

        $data = json_decode($response, true);

        if (isset($data['error'])) {
            return 'AI error: ' . ($data['error']['message'] ?? 'Unknown error');
        }

        return $data['content'][0]['text'] ?? '';
    }
}
