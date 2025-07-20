<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\SnapshotStats;
use Tests\TestModels\TestUser;

it('can get advanced event type analysis', function () {
    // Set storage to database for this test
    Grazulex\LaravelSnapshot\Snapshot::setStorage(new Grazulex\LaravelSnapshot\Storage\DatabaseStorage());

    $user = TestUser::factory()->create();

    // Create different types of snapshots directly in the database
    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'manual-1',
        'event_type' => 'manual',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'manual-2',
        'event_type' => 'manual',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'created-snapshot',
        'event_type' => 'created',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'updated-snapshot-1',
        'event_type' => 'updated',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'updated-snapshot-2',
        'event_type' => 'updated',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    $stats = SnapshotStats::new($user)
        ->eventTypeAnalysis()
        ->get();

    expect($stats['event_type_counts'])->toHaveKey('manual');
    expect($stats['event_type_counts']['manual'])->toBe(2);
    expect($stats['event_type_counts']['created'])->toBe(1);
    expect($stats['event_type_counts']['updated'])->toBe(2);

    expect($stats['event_type_percentages'])->toHaveKey('manual');
    expect($stats['event_type_percentages']['manual'])->toBe(40.0); // 2/5 * 100

    expect($stats['most_recent_by_event_type'])->toHaveKey('manual');
    expect($stats['most_recent_by_event_type'])->toHaveKey('created');
});

it('can get comprehensive change frequency analysis', function () {
    // Set storage to database for this test
    Grazulex\LaravelSnapshot\Snapshot::setStorage(new Grazulex\LaravelSnapshot\Storage\DatabaseStorage());

    $user = TestUser::factory()->create();

    // Create snapshots over time directly in database
    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'test-1',
        'event_type' => 'manual',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'test-2',
        'event_type' => 'manual',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'test-3',
        'event_type' => 'manual',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    $stats = SnapshotStats::new($user)
        ->changeFrequency()
        ->get();

    expect($stats)->toHaveKey('changes_by_day');
    expect($stats)->toHaveKey('changes_by_week');
    expect($stats)->toHaveKey('changes_by_month');
    expect($stats)->toHaveKey('average_changes_per_day');

    expect($stats['average_changes_per_day'])->toBeGreaterThan(0);
});

it('can combine multiple stat methods', function () {
    // Set storage to database for this test
    Grazulex\LaravelSnapshot\Snapshot::setStorage(new Grazulex\LaravelSnapshot\Storage\DatabaseStorage());

    $user = TestUser::factory()->create();

    Grazulex\LaravelSnapshot\Models\ModelSnapshot::create([
        'model_type' => get_class($user),
        'model_id' => $user->id,
        'label' => 'test',
        'event_type' => 'manual',
        'data' => ['attributes' => $user->toArray()],
        'metadata' => [],
    ]);

    $stats = SnapshotStats::new($user)
        ->counters()
        ->mostChangedFields()
        ->changeFrequency()
        ->eventTypeAnalysis()
        ->get();

    expect($stats)->toHaveKey('total_snapshots');
    expect($stats)->toHaveKey('snapshots_by_event');
    expect($stats)->toHaveKey('most_changed_fields');
    expect($stats)->toHaveKey('changes_by_day');
    expect($stats)->toHaveKey('event_type_counts');
});

it('can get stats for all models when no specific model provided', function () {
    TestUser::factory()->count(2)->create()->each->snapshot('test');

    $stats = SnapshotStats::new()
        ->counters()
        ->get();

    expect($stats['total_snapshots'])->toBe(2);
});

it('handles models with no snapshots gracefully', function () {
    $user = TestUser::factory()->create();

    $stats = SnapshotStats::new($user)
        ->counters()
        ->mostChangedFields()
        ->changeFrequency()
        ->eventTypeAnalysis()
        ->get();

    expect($stats['total_snapshots'])->toBe(0);
    expect($stats['most_changed_fields'])->toBe([]);
    expect($stats['event_type_counts'])->toBe([]);
});
