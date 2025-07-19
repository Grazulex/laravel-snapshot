<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Storage\FileStorage;

describe('ArrayStorage', function () {
    beforeEach(function () {
        ArrayStorage::clearAll();
    });

    test('it can save and load data', function () {
        $storage = new ArrayStorage();
        $data = ['test' => 'value'];

        $saved = $storage->save('test-label', $data);
        $loaded = $storage->load('test-label');

        expect($saved)->toEqual($data)
            ->and($loaded)->toEqual($data);
    });

    test('it returns null for non-existent data', function () {
        $storage = new ArrayStorage();

        expect($storage->load('non-existent'))->toBeNull();
    });

    test('it can list saved data', function () {
        $storage = new ArrayStorage();

        $storage->save('label1', ['data' => 1]);
        $storage->save('label2', ['data' => 2]);

        $list = $storage->list();

        expect($list)->toHaveCount(2)
            ->and($list[0])->toHaveKey('label')
            ->and($list[0])->toHaveKey('data');
    });

    test('it can delete data', function () {
        $storage = new ArrayStorage();

        $storage->save('to-delete', ['test' => 1]);
        expect($storage->load('to-delete'))->not->toBeNull();

        $deleted = $storage->delete('to-delete');

        expect($deleted)->toBeTrue()
            ->and($storage->load('to-delete'))->toBeNull();
    });

    test('it can clear all data', function () {
        $storage = new ArrayStorage();

        $storage->save('test1', ['data' => 1]);
        $storage->save('test2', ['data' => 2]);

        $cleared = $storage->clear();

        expect($cleared)->toBe(2)
            ->and($storage->list())->toHaveCount(0);
    });

    test('it can clear data by model class', function () {
        $storage = new ArrayStorage();

        $storage->save('user1', ['class' => 'User', 'data' => 1]);
        $storage->save('post1', ['class' => 'Post', 'data' => 2]);

        $cleared = $storage->clear('User');

        expect($cleared)->toBe(1)
            ->and($storage->list())->toHaveCount(1);
    });
});

describe('FileStorage', function () {
    beforeEach(function () {
        // Clean up test files
        $testDir = storage_path('snapshots');
        if (is_dir($testDir)) {
            array_map('unlink', glob("$testDir/*.json"));
        }
    });

    test('it can save and load data', function () {
        $storage = new FileStorage();
        $data = ['test' => 'file_value'];

        $saved = $storage->save('file-test', $data);
        $loaded = $storage->load('file-test');

        expect($saved)->toEqual($data)
            ->and($loaded)->toEqual($data);
    });

    test('it returns null for non-existent file', function () {
        $storage = new FileStorage();

        expect($storage->load('non-existent-file'))->toBeNull();
    });

    test('it can delete files', function () {
        $storage = new FileStorage();

        $storage->save('delete-file', ['test' => 'delete']);
        expect($storage->load('delete-file'))->not->toBeNull();

        $deleted = $storage->delete('delete-file');

        expect($deleted)->toBeTrue()
            ->and($storage->load('delete-file'))->toBeNull();
    });

    test('it can clear files', function () {
        $storage = new FileStorage();

        $storage->save('clear1', ['class' => 'User', 'data' => 1]);
        $storage->save('clear2', ['class' => 'Post', 'data' => 2]);

        $cleared = $storage->clear();

        expect($cleared)->toBe(2)
            ->and($storage->list())->toHaveCount(0);
    });
});
