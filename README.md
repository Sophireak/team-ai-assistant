# Team AI Assistant

An AI-powered development assistant for Laravel teams. Automatically checks code quality, explains issues in plain language, and helps maintain consistency — before every `git push`.

---

## Installation

```bash
composer require yourname/team-ai-assistant
```

Run the setup wizard:

```bash
php artisan ai:setup
```

That's it. The wizard will walk you through publishing config, adding your API key, and activating the git hook.

---

## Commands

### `php artisan ai:check`
Runs all pre-push checks. Automatically triggered before every `git push` once the hook is active.

```bash
# Check and auto-fix what's possible
php artisan ai:check --fix

# Check only, no AI explanations (faster, works offline)
php artisan ai:check --skip-ai

# Bypass all checks
php artisan ai:check --force
```

### `php artisan ai:review`
AI-powered code review of staged changes or a specific file.

```bash
# Review staged changes
php artisan ai:review --staged

# Review a specific file
php artisan ai:review app/Models/Order.php

# Review the last commit
php artisan ai:review --last
```

### `php artisan ai:setup`
Interactive first-time setup wizard. Run once after cloning.

---

## What it checks

| Check | Auto-fixable |
|---|---|
| `.env` file exists | ✅ Copies from `.env.example` |
| `APP_KEY` is set | ✅ Runs `key:generate` |
| All `.env.example` keys present | ⚠ Warns |
| Critical DB keys filled in | ⚠ Warns |
| Pending migrations | ✅ Runs `migrate` |
| `node_modules` installed | ✅ Runs `npm install` |
| `public/build` compiled | ✅ Runs `npm run build` |
| Stale assets | ✅ Rebuilds |
| `public/storage` symlink | ✅ Runs `storage:link` |
| Storage dirs writable | ✅ `chmod 775` |
| `vendor/` exists | ✅ `composer install` |
| `APP_DEBUG` safe for environment | ⚠ Warns |
| Direct push to `main`/`master` | ⚠ Warns |
| Uncommitted files | ⚠ Shows list |

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=team-ai-assistant-config
```

Edit `config/team-ai-assistant.php`:

```php
return [
    'anthropic_api_key'  => env('ANTHROPIC_API_KEY'),
    'model'              => 'claude-sonnet-4-20250514',
    'protected_branches' => ['main', 'master'],
    'auto_fix'           => true,

    // Add or remove analyzers here
    'analyzers' => [
        \YourName\TeamAIAssistant\Analyzers\EnvAnalyzer::class,
        \YourName\TeamAIAssistant\Analyzers\MigrationAnalyzer::class,
        \YourName\TeamAIAssistant\Analyzers\AssetAnalyzer::class,
        \YourName\TeamAIAssistant\Analyzers\StorageAnalyzer::class,
        \YourName\TeamAIAssistant\Analyzers\ComposerAnalyzer::class,
    ],
];
```

---

## Writing a custom analyzer

Create a class that implements `AnalyzerInterface`:

```php
use YourName\TeamAIAssistant\Analyzers\Contracts\AnalyzerInterface;

class MyCustomAnalyzer implements AnalyzerInterface
{
    public function name(): string
    {
        return 'My custom check';
    }

    public function analyze(bool $autoFix = false): array
    {
        $issues   = [];
        $warnings = [];
        $fixed    = [];

        // your logic here
        if (! file_exists(base_path('.custom-file'))) {
            $issues[] = '.custom-file is missing.';
        }

        return compact('issues', 'warnings', 'fixed');
    }
}
```

Register it in `config/team-ai-assistant.php` under `analyzers`.

---

## Team setup

**You (once):**
```bash
composer require yourname/team-ai-assistant
php artisan ai:setup
git add .githooks/ config/team-ai-assistant.php
git commit -m "Add team-ai-assistant"
git push
```

**Every teammate (once, after cloning):**
```bash
composer install
php artisan ai:setup
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT
