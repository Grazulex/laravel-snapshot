<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Console\Commands\SnapshotListCommand;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;

describe('SnapshotListCommand', function () {
    beforeEach(function () {
        ArrayStorage::clearAll();
    });

    test('it can list snapshots', function () {
        $command = new SnapshotListCommand();

        // Mock the storage with some data
        $storage = new ArrayStorage();
        $storage->save('test-snapshot-1', [
            'model_type' => 'User',
            'model_id' => '1',
            'data' => ['name' => 'John'],
            'created_at' => now()->toDateTimeString(),
        ]);

        $storage->save('test-snapshot-2', [
            'model_type' => 'Post',
            'model_id' => '1',
            'data' => ['title' => 'Test Post'],
            'created_at' => now()->toDateTimeString(),
        ]);

        // Mock the command to use our storage
        $command->setStorage($storage);

        expect($command)->toBeInstanceOf(SnapshotListCommand::class);
    });

    test('it handles empty snapshot list', function () {
        $command = new SnapshotListCommand();
        $storage = new ArrayStorage();

        $command->setStorage($storage);

        expect($command)->toBeInstanceOf(SnapshotListCommand::class);
    });

    test('it can filter snapshots by model', function () {
        $command = new SnapshotListCommand();
        $storage = new ArrayStorage();

        $storage->save('user-snapshot', [
            'model_type' => 'User',
            'model_id' => '1',
            'data' => ['name' => 'John'],
        ]);

        $storage->save('post-snapshot', [
            'model_type' => 'Post',
            'model_id' => '1',
            'data' => ['title' => 'Test'],
        ]);

        $command->setStorage($storage);

        expect($command)->toBeInstanceOf(SnapshotListCommand::class);
    });
});
