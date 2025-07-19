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

        // Run the package migration
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    final public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__.'/../database/migrations/2025_01_19_000000_create_snapshots_table.php';
        $migration->up();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelSnapshotServiceProvider::class,
        ];
    }
}
