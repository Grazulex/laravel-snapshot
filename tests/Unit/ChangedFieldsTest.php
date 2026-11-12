<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\SnapshotStats;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Tests\TestModels\TestUser;

beforeEach(function () {
    Snapshot::setStorage(new ArrayStorage());
    $this->freezeTime();
});

function latestSnapshotMetadata(TestUser $user): array
{
    return ModelSnapshot::query()
        ->where('model_type', TestUser::class)
        ->where('model_id', $user->getKey())
        ->orderBy('id', 'desc')
        ->firstOrFail()
        ->metadata;
}

it('stores an empty changed_fields list for the first snapshot of a model', function () {
    $user = TestUser::factory()->create();

    Snapshot::save($user, 'first');

    $metadata = latestSnapshotMetadata($user);

    expect($metadata)->toHaveKey('changed_fields')
        ->and($metadata['changed_fields'])->toBe([])
        ->and($metadata)->toHaveKeys(['created_by', 'ip_address', 'user_agent']);
});

it('stores the fields changed since the previous snapshot', function () {
    $user = TestUser::factory()->create(['name' => 'Before']);
    Snapshot::save($user, 'before');

    $user->update(['name' => 'After']);
    Snapshot::save($user, 'after');

    expect(latestSnapshotMetadata($user)['changed_fields'])->toBe(['name']);
});

it('stores an empty list when nothing changed between snapshots', function () {
    $user = TestUser::factory()->create();
    Snapshot::save($user, 'one');
    Snapshot::save($user, 'two');

    expect(latestSnapshotMetadata($user)['changed_fields'])->toBe([]);
});

it('only compares with snapshots of the same model instance', function () {
    $alice = TestUser::factory()->create(['name' => 'Alice']);
    $bob = TestUser::factory()->create(['name' => 'Bob']);

    Snapshot::save($alice, 'alice-1');
    Snapshot::save($bob, 'bob-1');

    expect(latestSnapshotMetadata($bob)['changed_fields'])->toBe([]);
});

it('ignores a previous snapshot without attributes', function () {
    $user = TestUser::factory()->create();

    ModelSnapshot::create([
        'model_type' => TestUser::class,
        'model_id' => $user->getKey(),
        'label' => 'legacy',
        'event_type' => 'manual',
        'data' => ['something' => 'else'],
        'metadata' => [],
    ]);

    Snapshot::save($user, 'current');

    expect(latestSnapshotMetadata($user)['changed_fields'])->toBe([]);
});

it('feeds the most_changed_fields statistic', function () {
    $user = TestUser::factory()->create(['name' => 'One', 'email' => 'one@example.test']);
    Snapshot::save($user, 's1');

    $user->update(['name' => 'Two']);
    Snapshot::save($user, 's2');

    $user->update(['name' => 'Three', 'email' => 'three@example.test']);
    Snapshot::save($user, 's3');

    $stats = SnapshotStats::new($user)->mostChangedFields()->get();

    expect($stats['most_changed_fields'])->toBe([
        'name' => 2,
        'email' => 1,
    ]);
});
