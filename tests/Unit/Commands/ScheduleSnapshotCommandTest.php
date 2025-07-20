<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Console\Commands\ScheduleSnapshotCommand;
use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Tests\TestModels\TestUser;

it('can create scheduled snapshot for specific model', function () {
    $user = TestUser::factory()->create();

    $this->artisan(ScheduleSnapshotCommand::class, [
        'model' => TestUser::class,
        '--id' => $user->id,
        '--label' => 'daily-backup',
    ])
        ->assertExitCode(0);

    $snapshot = ModelSnapshot::where('model_type', TestUser::class)
        ->where('model_id', $user->id)
        ->where('event_type', 'scheduled')
        ->first();

    expect($snapshot)->not->toBeNull();
    expect($snapshot->label)->toContain('daily-backup');
});

it('can create scheduled snapshots for all models', function () {
    TestUser::factory()->count(3)->create();

    $this->artisan(ScheduleSnapshotCommand::class, [
        'model' => TestUser::class,
        '--label' => 'weekly',
    ])
        ->expectsOutput('Creating scheduled snapshots for 3 models...')
        ->expectsOutput('Created: 3 snapshots')
        ->assertExitCode(0);

    $snapshots = ModelSnapshot::where('model_type', TestUser::class)
        ->where('event_type', 'scheduled')
        ->get();

    expect($snapshots)->toHaveCount(3);

    foreach ($snapshots as $snapshot) {
        expect($snapshot->label)->toContain('weekly');
    }
});

it('respects limit option', function () {
    TestUser::factory()->count(5)->create();

    $this->artisan(ScheduleSnapshotCommand::class, [
        'model' => TestUser::class,
        '--limit' => 2,
    ])
        ->expectsOutput('Creating scheduled snapshots for 2 models...')
        ->assertExitCode(0);

    $snapshots = ModelSnapshot::where('model_type', TestUser::class)
        ->where('event_type', 'scheduled')
        ->get();

    expect($snapshots)->toHaveCount(2);
});

it('handles non-existent model class', function () {
    $this->artisan(ScheduleSnapshotCommand::class, [
        'model' => 'App\Models\NonExistentModel',
    ])
        ->expectsOutput('Model class App\Models\NonExistentModel does not exist.')
        ->assertExitCode(1);
});

it('handles empty model collection', function () {
    $this->artisan(ScheduleSnapshotCommand::class, [
        'model' => TestUser::class,
    ])
        ->expectsOutput('No models of type Tests\TestModels\TestUser found.')
        ->assertExitCode(0);
});

it('handles non-existent specific model', function () {
    $this->artisan(ScheduleSnapshotCommand::class, [
        'model' => TestUser::class,
        '--id' => 999,
    ])
        ->expectsOutput('Model Tests\TestModels\TestUser with ID 999 not found.')
        ->assertExitCode(1);
});
