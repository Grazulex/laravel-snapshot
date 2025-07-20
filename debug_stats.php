<?php

declare(strict_types=1);
require_once 'vendor/autoload.php';

use Tests\TestModels\TestUser;

// Set up in-memory database
$app = new Illuminate\Foundation\Application(__DIR__);
$app->singleton('db', fn () => new Illuminate\Database\DatabaseManager($app, new Illuminate\Database\DatabaseServiceProvider($app)));

config()->set('database.default', 'testing');
config()->set('database.connections.testing', [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
]);

$user = TestUser::factory()->create();
$user->snapshot('manual-1');
$snapshots = Grazulex\LaravelSnapshot\Models\ModelSnapshot::all();
print_r($snapshots->toArray());
