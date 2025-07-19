<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\SnapshotStats;

test('it can create stats instance', function () {
    $stats = new SnapshotStats();

    expect($stats)->toBeInstanceOf(SnapshotStats::class);
});

test('it can create stats instance with model', function () {
    $model = new class extends Illuminate\Database\Eloquent\Model
    {
        public $id = 1;

        protected $table = 'test_models';

        public function getKey()
        {
            return $this->id;
        }
    };

    $stats = new SnapshotStats($model);

    expect($stats)->toBeInstanceOf(SnapshotStats::class);
});

test('it can get most changed fields without DB', function () {
    $stats = new SnapshotStats();
    $result = $stats->mostChangedFields()->get();

    expect($result)->toHaveKey('most_changed_fields')
        ->and($result['most_changed_fields'])->toBeArray();
});

test('it can be converted to JSON', function () {
    $stats = new SnapshotStats();
    $stats->mostChangedFields(); // Add some data
    $json = $stats->toJson();

    expect($json)->toBeString();

    $decoded = json_decode($json, true);
    expect($decoded)->toBeArray()
        ->and($decoded)->toHaveKey('most_changed_fields');
});

test('it can chain multiple stat methods', function () {
    $stats = new SnapshotStats();
    $result = $stats->mostChangedFields()->get();

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('most_changed_fields');
});

test('it handles empty get gracefully', function () {
    $stats = new SnapshotStats();
    $result = $stats->get();

    expect($result)->toBeArray();
});
