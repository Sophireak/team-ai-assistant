<?php

namespace YourName\TeamAIAssistant\Tests\Feature;

use Mockery;
use YourName\TeamAIAssistant\Services\AIService;
use YourName\TeamAIAssistant\Services\Contracts\AIServiceInterface;
use YourName\TeamAIAssistant\Tests\TestCase;

class CheckCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the AI service so tests never hit the real API
        $mock = Mockery::mock(AIServiceInterface::class);
        $mock->shouldReceive('isAvailable')->andReturn(false);
        $mock->shouldReceive('explainIssues')->andReturn('');
        $mock->shouldReceive('reviewCode')->andReturn('');

        $this->app->instance(AIService::class, $mock);
        $this->app->instance(AIServiceInterface::class, $mock);
    }

    /** @test */
    public function command_runs_without_error(): void
    {
        $this->artisan('ai:check', ['--skip-ai' => true, '--force' => true])
             ->assertExitCode(0);
    }

    /** @test */
    public function command_shows_banner(): void
    {
        $this->artisan('ai:check', ['--skip-ai' => true, '--force' => true])
             ->expectsOutputToContain('Team AI Assistant')
             ->assertExitCode(0);
    }

    /** @test */
    public function force_flag_bypasses_all_checks(): void
    {
        $this->artisan('ai:check', ['--force' => true])
             ->assertExitCode(0);
    }

    /** @test */
    public function skip_ai_flag_suppresses_ai_prompt(): void
    {
        // With --skip-ai there should be no "Want the AI to explain" prompt
        $this->artisan('ai:check', ['--skip-ai' => true, '--force' => true])
             ->doesntExpectOutputToContain('Want the AI')
             ->assertExitCode(0);
    }

    /** @test */
    public function command_is_registered(): void
    {
        $this->assertTrue(
            collect($this->app->make('Illuminate\Contracts\Console\Kernel')->all())
                ->has('ai:check')
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
