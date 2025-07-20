<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it can create model snapshot', function () {
    $snapshot = ModelSnapshot::create([
        'model_type' => 'App\Models\User',
        'model_id' => '1',
        'label' => 'test-snapshot',
        'event_type' => 'manual',
        'data' => ['name' => 'John', 'email' => 'john@example.com'],
        'metadata' => ['created_by' => 1],
    ]);

    expect($snapshot)->toBeInstanceOf(ModelSnapshot::class)
        ->and($snapshot->model_type)->toBe('App\Models\User')
        ->and($snapshot->model_id)->toBe('1')
        ->and($snapshot->label)->toBe('test-snapshot')
        ->and($snapshot->event_type)->toBe('manual')
        ->and($snapshot->data)->toEqual(['name' => 'John', 'email' => 'john@example.com'])
        ->and($snapshot->metadata)->toEqual(['created_by' => 1]);
});

test('it casts data and metadata as arrays', function () {
    $snapshot = ModelSnapshot::create([
        'model_type' => 'App\Models\User',
        'model_id' => '1',
        'label' => 'cast-test',
        'event_type' => 'auto',
        'data' => ['test' => 'data'],
        'metadata' => ['test' => 'meta'],
    ]);

    expect($snapshot->data)->toBeArray()
        ->and($snapshot->metadata)->toBeArray()
        ->and($snapshot->data)->toEqual(['test' => 'data'])
        ->and($snapshot->metadata)->toEqual(['test' => 'meta']);
});

test('it can scope by model type', function () {
    ModelSnapshot::create([
        'model_type' => 'App\Models\User',
        'model_id' => '1',
        'label' => 'user-snapshot',
        'event_type' => 'manual',
        'data' => ['type' => 'user'],
        'metadata' => [],
    ]);

    ModelSnapshot::create([
        'model_type' => 'App\Models\Post',
        'model_id' => '1',
        'label' => 'post-snapshot',
        'event_type' => 'manual',
        'data' => ['type' => 'post'],
        'metadata' => [],
    ]);

    $userSnapshots = ModelSnapshot::forModel('App\Models\User')->get();
    $postSnapshots = ModelSnapshot::forModel('App\Models\Post')->get();

    expect($userSnapshots)->toHaveCount(1)
        ->and($postSnapshots)->toHaveCount(1)
        ->and($userSnapshots->first()->model_type)->toBe('App\Models\User')
        ->and($postSnapshots->first()->model_type)->toBe('App\Models\Post');
});

test('it can scope by event type', function () {
    ModelSnapshot::create([
        'model_type' => 'App\Models\User',
        'model_id' => '1',
        'label' => 'manual-snapshot',
        'event_type' => 'manual',
        'data' => ['event' => 'manual'],
        'metadata' => [],
    ]);

    ModelSnapshot::create([
        'model_type' => 'App\Models\User',
        'model_id' => '1',
        'label' => 'auto-snapshot',
        'event_type' => 'created',
        'data' => ['event' => 'auto'],
        'metadata' => [],
    ]);

    $manualSnapshots = ModelSnapshot::forEvent('manual')->get();
    $autoSnapshots = ModelSnapshot::forEvent('created')->get();

    expect($manualSnapshots)->toHaveCount(1)
        ->and($autoSnapshots)->toHaveCount(1)
        ->and($manualSnapshots->first()->event_type)->toBe('manual')
        ->and($autoSnapshots->first()->event_type)->toBe('created');
});

test('it can scope by label', function () {
    ModelSnapshot::create([
        'model_type' => 'App\Models\User',
        'model_id' => '1',
        'label' => 'specific-label',
        'event_type' => 'manual',
        'data' => ['test' => 1],
        'metadata' => [],
    ]);

    ModelSnapshot::create([
        'model_type' => 'App\Models\User',
        'model_id' => '1',
        'label' => 'another-label',
        'event_type' => 'manual',
        'data' => ['test' => 2],
        'metadata' => [],
    ]);

    $specificSnapshots = ModelSnapshot::withLabel('specific-label')->get();

    expect($specificSnapshots)->toHaveCount(1)
        ->and($specificSnapshots->first()->label)->toBe('specific-label');
});

test('it has correct fillable attributes', function () {
    $snapshot = new ModelSnapshot();
    $fillable = $snapshot->getFillable();

    expect($fillable)->toContain('model_type')
        ->and($fillable)->toContain('model_id')
        ->and($fillable)->toContain('label')
        ->and($fillable)->toContain('event_type')
        ->and($fillable)->toContain('data')
        ->and($fillable)->toContain('metadata');
});

test('it uses correct table name', function () {
    $snapshot = new ModelSnapshot();

    expect($snapshot->getTable())->toBe('snapshots');
});

test('it has correct casts configuration', function () {
    $snapshot = new ModelSnapshot();
    $casts = $snapshot->getCasts();

    expect($casts)->toHaveKey('data')
        ->and($casts)->toHaveKey('metadata')
        ->and($casts['data'])->toBe('array')
        ->and($casts['metadata'])->toBe('array');
});
