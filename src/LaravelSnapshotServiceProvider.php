<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot;

use Illuminate\Support\ServiceProvider;

final class LaravelSnapshotServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/Config/snapshot.php' => config_path('snapshot.php'),
        ], 'snapshot-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'snapshot-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\SaveSnapshotCommand::class,
                Console\Commands\DiffSnapshotCommand::class,
                Console\Commands\ListSnapshotsCommand::class,
                Console\Commands\GenerateReportCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/snapshot.php', 'snapshot');
    }
}
