<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;

beforeEach(function () {
    // Reset array storage before each test
    ArrayStorage::clearAll();

    // Use array storage for tests (no DB needed)
    Snapshot::setStorage(new ArrayStorage());
});

test('it can save and load snapshots with array storage', function () {
    $data = ['name' => 'John', 'age' => 30];

    $saved = Snapshot::save($data, 'test-snapshot');
    $loaded = Snapshot::load('test-snapshot');

    expect($saved)->toBeArray()
        ->and($loaded)->toBeArray()
        ->and($loaded['data'])->toEqual($data);
});

test('it can create automatic snapshots', function () {
    $data = ['name' => 'Jane', 'status' => 'active'];

    $snapshot = Snapshot::auto($data, 'created');

    expect($snapshot)->toBeArray()
        ->and($snapshot['event_type'])->toBe('created')
        ->and($snapshot['data'])->toEqual($data);
});

test('it can create scheduled snapshots', function () {
    $data = ['name' => 'Bob', 'role' => 'admin'];

    $snapshot = Snapshot::scheduled($data, 'daily');

    expect($snapshot)->toBeArray()
        ->and($snapshot['event_type'])->toBe('scheduled')
        ->and($snapshot['data'])->toEqual($data);
});

test('it can list snapshots', function () {
    Snapshot::save(['test' => 1], 'snapshot1');
    Snapshot::save(['test' => 2], 'snapshot2');

    $snapshots = Snapshot::list();

    expect($snapshots)->toHaveCount(2)
        ->and($snapshots[0])->toHaveKey('label')
        ->and($snapshots[1])->toHaveKey('label');
});

test('it can delete snapshots', function () {
    Snapshot::save(['test' => 1], 'to-delete');

    expect(Snapshot::load('to-delete'))->not->toBeNull();

    $deleted = Snapshot::delete('to-delete');

    expect($deleted)->toBeTrue()
        ->and(Snapshot::load('to-delete'))->toBeNull();
});

test('it can clear snapshots', function () {
    Snapshot::save(['test' => 1], 'snap1');
    Snapshot::save(['test' => 2], 'snap2');

    $cleared = Snapshot::clear();

    expect($cleared)->toBe(2)
        ->and(Snapshot::list())->toHaveCount(0);
});

test('it can compare snapshots', function () {
    $dataA = ['name' => 'John', 'age' => 30];
    $dataB = ['name' => 'John', 'age' => 31, 'city' => 'Paris'];

    Snapshot::save($dataA, 'snapA');
    Snapshot::save($dataB, 'snapB');

    $diff = Snapshot::diff('snapA', 'snapB');

    expect($diff)->toHaveKey('modified')
        ->and($diff)->toHaveKey('added')
        ->and($diff['modified']['age']['from'])->toBe(30)
        ->and($diff['modified']['age']['to'])->toBe(31)
        ->and($diff['added']['city'])->toBe('Paris');
});

test('it throws exception for non-existent snapshot in diff', function () {
    expect(fn () => Snapshot::diff('non-existent', 'also-non-existent'))
        ->toThrow(InvalidArgumentException::class);
});

test('it can serialize different data types', function () {
    $array = ['key' => 'value'];
    $object = (object) ['prop' => 'value'];
    $string = 'simple string';

    $serializedArray = Snapshot::serializeModel($array);
    $serializedObject = Snapshot::serializeModel($object);
    $serializedString = Snapshot::serializeModel($string);

    expect($serializedArray)->toHaveKey('class')
        ->and($serializedArray['class'])->toBe('array')
        ->and($serializedObject)->toHaveKey('class')
        ->and($serializedObject['class'])->toBe('stdClass')
        ->and($serializedString)->toHaveKey('class')
        ->and($serializedString['class'])->toBe('string');
});

test('it can calculate diff between two data arrays', function () {
    $dataA = ['name' => 'John', 'age' => 30, 'city' => 'London'];
    $dataB = ['name' => 'John', 'age' => 31, 'country' => 'UK'];

    $diff = Snapshot::calculateDiff($dataA, $dataB);

    expect($diff)->toHaveKey('modified')
        ->and($diff)->toHaveKey('added')
        ->and($diff)->toHaveKey('removed')
        ->and($diff['modified']['age']['from'])->toBe(30)
        ->and($diff['modified']['age']['to'])->toBe(31)
        ->and($diff['added']['country'])->toBe('UK')
        ->and($diff['removed']['city'])->toBe('London');
});

test('it can use different storage drivers', function () {
    // Test array storage
    Snapshot::setStorage(new ArrayStorage());
    Snapshot::save(['test' => 'array'], 'array-test');
    expect(Snapshot::load('array-test'))->not->toBeNull();

    // Test that we can switch storage instances
    // Note: ArrayStorage uses static variables, so data persists across instances
    $newStorage = new ArrayStorage();
    Snapshot::setStorage($newStorage);
    expect(Snapshot::load('array-test'))->not->toBeNull(); // Same data in static storage

    // Clear to test different behavior
    ArrayStorage::clearAll();
    expect(Snapshot::load('array-test'))->toBeNull(); // Now it should be null
});
