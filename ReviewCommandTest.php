<?php

namespace YourName\TeamAIAssistant\Tests\Feature;

use Mockery;
use YourName\TeamAIAssistant\Services\AIService;
use YourName\TeamAIAssistant\Services\Contracts\AIServiceInterface;
use YourName\TeamAIAssistant\Tests\TestCase;

class ReviewCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock AI service — returns a canned review so tests are fast and free
        $mock = Mockery::mock(AIServiceInterface::class);
        $mock->shouldReceive('isAvailable')->andReturn(true);
        $mock->shouldReceive('reviewCode')->andReturn(
            "**Critical**\n- No issues found\n\n**Suggestions**\n- Add return type hints"
        );

        $this->app->instance(AIService::class, $mock);
        $this->app->instance(AIServiceInterface::class, $mock);
    }

    /** @test */
    public function command_is_registered(): void
    {
        $this->assertTrue(
            collect($this->app->make('Illuminate\Contracts\Console\Kernel')->all())
                ->has('ai:review')
        );
    }

    /** @test */
    public function it_errors_when_api_key_missing(): void
    {
        $mock = Mockery::mock(AIServiceInterface::class);
        $mock->shouldReceive('isAvailable')->andReturn(false);

        $this->app->instance(AIService::class, $mock);
        $this->app->instance(AIServiceInterface::class, $mock);

        $this->artisan('ai:review', ['file' => 'composer.json'])
             ->expectsOutputToContain('ANTHROPIC_API_KEY')
             ->assertExitCode(1);
    }

    /** @test */
    public function it_reviews_an_existing_file(): void
    {
        // composer.json always exists in the test environment
        $this->artisan('ai:review', ['file' => 'composer.json'])
             ->expectsOutputToContain('AI Code Review')
             ->assertExitCode(0);
    }

    /** @test */
    public function it_errors_on_missing_file(): void
    {
        $this->artisan('ai:review', ['file' => 'non-existent-file.php'])
             ->assertExitCode(1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
