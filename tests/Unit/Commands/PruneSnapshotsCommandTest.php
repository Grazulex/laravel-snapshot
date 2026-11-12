<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Console\Commands\PruneSnapshotsCommand;
use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Tests\TestModels\TestUser;

beforeEach(function () {
    $this->makeSnapshot = function (string $label, int $daysAgo, string $modelType = TestUser::class): ModelSnapshot {
        $snapshot = ModelSnapshot::create([
            'model_type' => $modelType,
            'model_id' => '1',
            'label' => $label,
            'event_type' => 'manual',
            'data' => ['id' => 1],
            'metadata' => [],
        ]);

        $snapshot->created_at = now()->subDays($daysAgo);
        $snapshot->save();

        return $snapshot;
    };

    config()->set('snapshot.retention.enabled', true);
    config()->set('snapshot.retention.days', 30);
});

it('is registered as an artisan command', function () {
    $this->artisan('snapshot:prune', ['--dry-run' => true])->assertExitCode(0);
});

it('deletes snapshots older than the configured retention', function () {
    ($this->makeSnapshot)('old-1', 45);
    ($this->makeSnapshot)('old-2', 31);
    ($this->makeSnapshot)('recent', 5);

    $this->artisan(PruneSnapshotsCommand::class)
        ->expectsOutput('Pruned 2 snapshots older than 30 days.')
        ->assertExitCode(0);

    expect(ModelSnapshot::pluck('label')->all())->toBe(['recent']);
});

it('uses the --days option over the configured retention', function () {
    ($this->makeSnapshot)('old', 45);
    ($this->makeSnapshot)('mid', 10);
    ($this->makeSnapshot)('recent', 2);

    $this->artisan(PruneSnapshotsCommand::class, ['--days' => '7'])
        ->expectsOutput('Pruned 2 snapshots older than 7 days.')
        ->assertExitCode(0);

    expect(ModelSnapshot::pluck('label')->all())->toBe(['recent']);
});

it('does not delete anything in dry-run mode', function () {
    ($this->makeSnapshot)('old', 45);
    ($this->makeSnapshot)('recent', 5);

    $this->artisan(PruneSnapshotsCommand::class, ['--dry-run' => true])
        ->expectsOutput('DRY RUN MODE - No snapshots will actually be deleted')
        ->expectsOutput('1 snapshots older than 30 days would be deleted.')
        ->assertExitCode(0);

    expect(ModelSnapshot::count())->toBe(2);
});

it('can be limited to a model class', function () {
    ($this->makeSnapshot)('old-user', 45);
    ($this->makeSnapshot)('old-other', 45, 'App\\Models\\Order');

    $this->artisan(PruneSnapshotsCommand::class, ['--model' => TestUser::class])
        ->expectsOutput('Pruned 1 snapshots older than 30 days.')
        ->assertExitCode(0);

    expect(ModelSnapshot::pluck('label')->all())->toBe(['old-other']);
});

it('skips pruning when retention is disabled', function () {
    config()->set('snapshot.retention.enabled', false);
    ($this->makeSnapshot)('old', 45);

    $this->artisan(PruneSnapshotsCommand::class)
        ->expectsOutput('Snapshot retention is disabled. Nothing to prune.')
        ->assertExitCode(0);

    expect(ModelSnapshot::count())->toBe(1);
});

it('prunes with an explicit --days even when retention is disabled', function () {
    config()->set('snapshot.retention.enabled', false);
    ($this->makeSnapshot)('old', 45);

    $this->artisan(PruneSnapshotsCommand::class, ['--days' => '30'])
        ->expectsOutput('Pruned 1 snapshots older than 30 days.')
        ->assertExitCode(0);

    expect(ModelSnapshot::count())->toBe(0);
});

it('reports when nothing needs to be pruned', function () {
    ($this->makeSnapshot)('recent', 5);

    $this->artisan(PruneSnapshotsCommand::class)
        ->expectsOutput('No snapshots older than 30 days found.')
        ->assertExitCode(0);

    expect(ModelSnapshot::count())->toBe(1);
});

it('fails on an invalid retention period', function (mixed $days) {
    ($this->makeSnapshot)('old', 45);

    $this->artisan(PruneSnapshotsCommand::class, ['--days' => $days])
        ->expectsOutput('The retention period must be a positive number of days.')
        ->assertExitCode(1);

    expect(ModelSnapshot::count())->toBe(1);
})->with(['0', '-5', 'abc']);

it('fails when the configured retention period is invalid', function () {
    config()->set('snapshot.retention.days', 0);

    $this->artisan(PruneSnapshotsCommand::class)
        ->expectsOutput('The retention period must be a positive number of days.')
        ->assertExitCode(1);
});

it('scopes snapshots older than a number of days', function () {
    ($this->makeSnapshot)('old', 45);
    ($this->makeSnapshot)('recent', 5);

    expect(ModelSnapshot::olderThanDays(30)->pluck('label')->all())->toBe(['old']);
});
