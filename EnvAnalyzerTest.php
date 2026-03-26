<?php

namespace YourName\TeamAIAssistant\Tests\Unit;

use YourName\TeamAIAssistant\Analyzers\EnvAnalyzer;
use YourName\TeamAIAssistant\Tests\TestCase;

class EnvAnalyzerTest extends TestCase
{
    private EnvAnalyzer $analyzer;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new EnvAnalyzer();
        $this->tempDir  = sys_get_temp_dir() . '/env-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        array_map('unlink', glob("{$this->tempDir}/*"));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    /** @test */
    public function it_reports_missing_env_file(): void
    {
        // Point base_path to a dir with no .env
        app()->useBasePath($this->tempDir);

        $result = $this->analyzer->analyze(autoFix: false);

        $this->assertNotEmpty($result['issues']);
        $this->assertStringContainsString('.env', $result['issues'][0]);
    }

    /** @test */
    public function it_auto_fixes_missing_env_by_copying_example(): void
    {
        // Create .env.example but no .env
        file_put_contents("{$this->tempDir}/.env.example", "APP_KEY=\nDB_HOST=127.0.0.1\n");
        app()->useBasePath($this->tempDir);

        $result = $this->analyzer->analyze(autoFix: true);

        $this->assertFileExists("{$this->tempDir}/.env");
        $this->assertNotEmpty($result['fixed']);
    }

    /** @test */
    public function it_detects_missing_keys_vs_example(): void
    {
        file_put_contents("{$this->tempDir}/.env.example", "APP_KEY=\nDB_HOST=\nNEW_KEY=\n");
        file_put_contents("{$this->tempDir}/.env", "APP_KEY=somekey\nDB_HOST=localhost\n");
        app()->useBasePath($this->tempDir);

        $result = $this->analyzer->analyze(autoFix: false);

        $this->assertTrue(
            collect($result['issues'])->contains(fn($i) => str_contains($i, 'NEW_KEY'))
        );
    }

    /** @test */
    public function it_passes_when_env_is_complete(): void
    {
        file_put_contents("{$this->tempDir}/.env.example", "APP_KEY=\nDB_HOST=\nDB_DATABASE=\nDB_USERNAME=\n");
        file_put_contents("{$this->tempDir}/.env", "APP_KEY=base64:abc\nDB_HOST=127.0.0.1\nDB_DATABASE=mydb\nDB_USERNAME=root\n");
        app()->useBasePath($this->tempDir);

        $result = $this->analyzer->analyze(autoFix: false);

        // Should have no blocking issues (may have warnings about APP_KEY format etc.)
        $envIssues = array_filter($result['issues'], fn($i) => str_contains($i, 'Missing keys'));
        $this->assertEmpty($envIssues);
    }

    /** @test */
    public function it_implements_analyzer_interface(): void
    {
        $this->assertInstanceOf(
            \YourName\TeamAIAssistant\Analyzers\Contracts\AnalyzerInterface::class,
            $this->analyzer
        );
    }

    /** @test */
    public function it_returns_a_human_readable_name(): void
    {
        $this->assertNotEmpty($this->analyzer->name());
        $this->assertIsString($this->analyzer->name());
    }
}
