<?php

namespace YourName\TeamAIAssistant\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use YourName\TeamAIAssistant\TeamAIAssistantServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            TeamAIAssistantServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use SQLite in-memory for tests so no real DB needed
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Create a temporary .env file for tests that need one.
     */
    protected function createTempEnv(array $values = []): string
    {
        $path    = sys_get_temp_dir() . '/.env.test.' . uniqid();
        $content = implode("\n", array_map(
            fn($k, $v) => "{$k}={$v}",
            array_keys($values),
            $values
        ));
        file_put_contents($path, $content);
        return $path;
    }

    /**
     * Clean up a temp file after a test.
     */
    protected function removeTempFile(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
