<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Console\Commands\RestoreSnapshotCommand;
use Tests\TestModels\TestUser;

it('can restore model from snapshot', function () {
    $user = TestUser::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    // Create a snapshot
    $user->snapshot('before-changes');

    // Make changes
    $user->update([
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    expect($user->fresh()->name)->toBe('Updated Name');

    // Restore from snapshot
    $snapshot = $user->snapshots()->first();

    $this->artisan(RestoreSnapshotCommand::class, [
        'model' => TestUser::class,
        'id' => $user->id,
        'snapshot' => $snapshot->id,
        '--force' => true,
    ])
        ->expectsOutput("Model successfully restored to snapshot '{$snapshot->label}'.")
        ->assertExitCode(0);

    // Verify restoration
    $restoredUser = $user->fresh();
    expect($restoredUser->name)->toBe('Original Name');
    expect($restoredUser->email)->toBe('original@example.com');
});

it('can restore using snapshot label', function () {
    $user = TestUser::factory()->create(['name' => 'Original']);

    $user->snapshot('my-label');
    $user->update(['name' => 'Updated']);

    $this->artisan(RestoreSnapshotCommand::class, [
        'model' => TestUser::class,
        'id' => $user->id,
        'snapshot' => 'my-label',
        '--force' => true,
    ])
        ->assertExitCode(0);

    expect($user->fresh()->name)->toBe('Original');
});

it('handles non-existent model', function () {
    $this->artisan(RestoreSnapshotCommand::class, [
        'model' => TestUser::class,
        'id' => 999,
        'snapshot' => 'any-snapshot',
        '--force' => true,
    ])
        ->expectsOutput('Model Tests\TestModels\TestUser with ID 999 not found.')
        ->assertExitCode(1);
});

it('handles invalid model class', function () {
    $this->artisan(RestoreSnapshotCommand::class, [
        'model' => 'App\Models\NonExistentModel',
        'id' => 1,
        'snapshot' => 'any-snapshot',
        '--force' => true,
    ])
        ->expectsOutput('Model class App\Models\NonExistentModel does not exist.')
        ->assertExitCode(1);
});

it('runs in dry-run mode', function () {
    $user = TestUser::factory()->create(['name' => 'Original']);

    $user->snapshot('test-snapshot');
    $user->update(['name' => 'Updated']);

    $snapshot = $user->snapshots()->first();

    $this->artisan(RestoreSnapshotCommand::class, [
        'model' => TestUser::class,
        'id' => $user->id,
        'snapshot' => $snapshot->id,
        '--dry-run' => true,
    ])
        ->expectsOutput('DRY RUN MODE - Model will not actually be restored')
        ->assertExitCode(0);

    // Model should remain unchanged
    expect($user->fresh()->name)->toBe('Updated');
});
