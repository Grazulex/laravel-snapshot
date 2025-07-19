<?php

declare(strict_types=1);

describe('Array Storage (Standalone)', function () {
    test('it can save and load data', function () {
        // Clear static data
        $reflection = new ReflectionClass(Grazulex\LaravelSnapshot\Storage\ArrayStorage::class);
        $property = $reflection->getProperty('snapshots');
        $property->setAccessible(true);
        $property->setValue(null, []);

        $storage = new Grazulex\LaravelSnapshot\Storage\ArrayStorage();

        $data = ['test' => 'value', 'number' => 42];
        $saved = $storage->save('test-label', $data);

        expect($saved)->toHaveKey('test')
            ->and($saved['test'])->toBe('value')
            ->and($saved['number'])->toBe(42);

        $loaded = $storage->load('test-label');
        expect($loaded)->toEqual($saved);
    });

    test('it returns null for non-existent data', function () {
        $reflection = new ReflectionClass(Grazulex\LaravelSnapshot\Storage\ArrayStorage::class);
        $property = $reflection->getProperty('snapshots');
        $property->setAccessible(true);
        $property->setValue(null, []);

        $storage = new Grazulex\LaravelSnapshot\Storage\ArrayStorage();

        expect($storage->load('non-existent'))->toBeNull();
    });

    test('it can list snapshots', function () {
        $reflection = new ReflectionClass(Grazulex\LaravelSnapshot\Storage\ArrayStorage::class);
        $property = $reflection->getProperty('snapshots');
        $property->setAccessible(true);
        $property->setValue(null, []);

        $storage = new Grazulex\LaravelSnapshot\Storage\ArrayStorage();

        $storage->save('test1', ['data' => 1]);
        $storage->save('test2', ['data' => 2]);

        $snapshots = $storage->list();
        expect($snapshots)->toHaveCount(2);
    });

    test('it can delete snapshots', function () {
        $reflection = new ReflectionClass(Grazulex\LaravelSnapshot\Storage\ArrayStorage::class);
        $property = $reflection->getProperty('snapshots');
        $property->setAccessible(true);
        $property->setValue(null, []);

        $storage = new Grazulex\LaravelSnapshot\Storage\ArrayStorage();

        $storage->save('to-delete', ['data' => 'test']);
        expect($storage->load('to-delete'))->not->toBeNull();

        $deleted = $storage->delete('to-delete');
        expect($deleted)->toBeTrue()
            ->and($storage->load('to-delete'))->toBeNull();
    });

    test('it can clear all snapshots', function () {
        $reflection = new ReflectionClass(Grazulex\LaravelSnapshot\Storage\ArrayStorage::class);
        $property = $reflection->getProperty('snapshots');
        $property->setAccessible(true);
        $property->setValue(null, []);

        $storage = new Grazulex\LaravelSnapshot\Storage\ArrayStorage();

        $storage->save('test1', ['data' => 1]);
        $storage->save('test2', ['data' => 2]);

        $cleared = $storage->clear();
        expect($cleared)->toBe(2)
            ->and($storage->list())->toHaveCount(0);
    });
});

describe('Snapshot Manager', function () {
    test('it can be instantiated', function () {
        $manager = new Grazulex\LaravelSnapshot\SnapshotManager();

        expect($manager)->toBeInstanceOf(Grazulex\LaravelSnapshot\SnapshotManager::class);
    });

    test('it uses ArrayStorage by default', function () {
        $manager = new Grazulex\LaravelSnapshot\SnapshotManager();

        $storage = $manager->getStorage();

        expect($storage)->toBeInstanceOf(Grazulex\LaravelSnapshot\Storage\ArrayStorage::class);
    });

    test('it can save and load data', function () {
        $manager = new Grazulex\LaravelSnapshot\SnapshotManager();

        $data = ['test' => 'manager_test'];
        $saved = $manager->save('manager-test', $data);
        $loaded = $manager->load('manager-test');

        expect($saved)->toEqual($loaded)
            ->and($loaded['test'])->toBe('manager_test');
    });
});
