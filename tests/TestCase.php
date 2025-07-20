<?php

declare(strict_types=1);

namespace Tests;

use Grazulex\LaravelSnapshot\LaravelSnapshotServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run package migrations after app is set up
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    final public function getEnvironmentSetUp($app): void
    {
        // Configure in-memory SQLite database for testing
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelSnapshotServiceProvider::class,
        ];
    }
}
