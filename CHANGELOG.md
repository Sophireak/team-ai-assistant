# Changelog

All notable changes to this package will be documented here.

## [1.0.0] - 2025-03-26

### Added
- `ai:check` command with auto-fix support
- `ai:review` command for AI-powered code review
- `ai:setup` interactive setup wizard
- `EnvAnalyzer` — checks `.env`, `APP_KEY`, missing keys, empty DB values
- `MigrationAnalyzer` — detects pending migrations
- `AssetAnalyzer` — checks `node_modules` and `public/build`
- `StorageAnalyzer` — checks symlink and directory permissions
- `ComposerAnalyzer` — checks `vendor/` and `composer.lock` sync
- `AIService` — Claude API integration with swappable interface
- `GitService` — git status, diff, branch detection
- `AnalyzerInterface` — contract for custom analyzers
- `AIServiceInterface` — contract for swappable AI providers
- Git hook stub published to `.githooks/pre-push`
- Config file with full customization support
