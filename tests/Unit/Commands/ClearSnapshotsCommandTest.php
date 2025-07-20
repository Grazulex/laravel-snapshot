<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Console\Commands\ClearSnapshotsCommand;
use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Tests\TestModels\TestUser;

it('can clear all snapshots', function () {
    // Create test snapshots
    TestUser::factory()->create()->snapshot('test-1');
    TestUser::factory()->create()->snapshot('test-2');
    TestUser::factory()->create()->snapshot('test-3');

    expect(ModelSnapshot::count())->toBe(3);

    // Run clear command
    $this->artisan(ClearSnapshotsCommand::class, ['--force' => true])
        ->expectsOutput('Found 3 snapshots matching the criteria.')
        ->expectsOutput('Successfully deleted 3 snapshots.')
        ->assertExitCode(0);

    expect(ModelSnapshot::count())->toBe(0);
});

it('can clear snapshots by model', function () {
    $user1 = TestUser::factory()->create();
    $user2 = TestUser::factory()->create();

    $user1->snapshot('user1-snapshot');
    $user2->snapshot('user2-snapshot');

    expect(ModelSnapshot::count())->toBe(2);

    // Clear snapshots for specific model ID
    $this->artisan(ClearSnapshotsCommand::class, [
        '--model' => TestUser::class,
        '--id' => $user1->id,
        '--force' => true,
    ])
        ->expectsOutput('Found 1 snapshots matching the criteria.')
        ->expectsOutput('Successfully deleted 1 snapshots.')
        ->assertExitCode(0);

    expect(ModelSnapshot::count())->toBe(1);
    expect((int) ModelSnapshot::first()->model_id)->toBe($user2->id);
});

it('can run dry-run mode', function () {
    TestUser::factory()->create()->snapshot('test-snapshot');

    expect(ModelSnapshot::count())->toBe(1);

    $this->artisan(ClearSnapshotsCommand::class, ['--dry-run' => true])
        ->expectsOutput('Found 1 snapshots matching the criteria.')
        ->expectsOutput('DRY RUN MODE - No snapshots will actually be deleted')
        ->assertExitCode(0);

    // Snapshot should still exist
    expect(ModelSnapshot::count())->toBe(1);
});

it('handles empty results gracefully', function () {
    $this->artisan(ClearSnapshotsCommand::class, ['--force' => true])
        ->expectsOutput('No snapshots found matching the criteria.')
        ->assertExitCode(0);
});
