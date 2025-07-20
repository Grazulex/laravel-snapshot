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

        // Create users table for testing
        $this->artisan('migrate', ['--database' => 'testing']);

        $this->createUsersTable();
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

    private function createUsersTable(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
