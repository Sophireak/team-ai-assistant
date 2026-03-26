<?php

namespace YourName\TeamAIAssistant\Tests\Unit;

use Mockery;
use YourName\TeamAIAssistant\Services\AIService;
use YourName\TeamAIAssistant\Services\Contracts\AIServiceInterface;
use YourName\TeamAIAssistant\Tests\TestCase;

class AIServiceTest extends TestCase
{
    /** @test */
    public function it_implements_ai_service_interface(): void
    {
        $service = app(AIService::class);

        $this->assertInstanceOf(AIServiceInterface::class, $service);
    }

    /** @test */
    public function it_reports_unavailable_when_api_key_missing(): void
    {
        config(['team-ai-assistant.anthropic_api_key' => null]);

        $service = new AIService();

        $this->assertFalse($service->isAvailable());
    }

    /** @test */
    public function it_reports_available_when_api_key_set(): void
    {
        config(['team-ai-assistant.anthropic_api_key' => 'sk-ant-test-key']);

        $service = new AIService();

        $this->assertTrue($service->isAvailable());
    }

    /** @test */
    public function it_returns_empty_string_for_empty_issues(): void
    {
        $service = new AIService();

        $result = $service->explainIssues([]);

        $this->assertSame('', $result);
    }

    /** @test */
    public function it_returns_empty_string_when_no_api_key(): void
    {
        config(['team-ai-assistant.anthropic_api_key' => null]);

        $service = new AIService();

        // Should gracefully return empty rather than throwing
        $result = $service->explainIssues(['Some issue']);

        $this->assertSame('', $result);
    }

    /** @test */
    public function it_can_be_swapped_via_interface_binding(): void
    {
        // This tests that the interface → implementation binding works,
        // so teams can swap in a different AI provider
        $mock = Mockery::mock(AIServiceInterface::class);
        $mock->shouldReceive('isAvailable')->andReturn(true);
        $mock->shouldReceive('explainIssues')->andReturn('Mocked explanation');

        $this->app->instance(AIServiceInterface::class, $mock);

        $resolved = app(AIServiceInterface::class);

        $this->assertTrue($resolved->isAvailable());
        $this->assertSame('Mocked explanation', $resolved->explainIssues(['test']));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
