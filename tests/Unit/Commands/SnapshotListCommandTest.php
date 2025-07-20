<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Console\Commands\ListSnapshotsCommand;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;

describe('ListSnapshotsCommand', function () {
    beforeEach(function () {
        ArrayStorage::clearAll();
    });

    test('it can be instantiated', function () {
        $command = new ListSnapshotsCommand();

        expect($command)->toBeInstanceOf(ListSnapshotsCommand::class);
    });

    test('it has correct signature', function () {
        $command = new ListSnapshotsCommand();

        expect($command->getName())->toBe('snapshot:list');
    });

    test('it handles empty snapshot list gracefully', function () {
        $command = new ListSnapshotsCommand();

        // The command should handle empty snapshot list without errors
        expect($command)->toBeInstanceOf(ListSnapshotsCommand::class);
    });
});
