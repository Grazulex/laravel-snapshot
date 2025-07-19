<?php

declare(strict_types=1);

use Grazulex\LaravelSnapshot\Console\Commands\DiffSnapshotCommand;
use Grazulex\LaravelSnapshot\Console\Commands\ListSnapshotsCommand;
use Grazulex\LaravelSnapshot\Console\Commands\SaveSnapshotCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('save snapshot command exists', function () {
    expect(class_exists(SaveSnapshotCommand::class))->toBeTrue();
});

test('list snapshots command exists', function () {
    expect(class_exists(ListSnapshotsCommand::class))->toBeTrue();
});

test('diff snapshot command exists', function () {
    expect(class_exists(DiffSnapshotCommand::class))->toBeTrue();
});

test('commands are properly registered', function () {
    $commands = [
        'snapshot:save',
        'snapshot:list',
        'snapshot:diff',
    ];

    $allCommands = Artisan::all();

    foreach ($commands as $command) {
        expect($allCommands)->toHaveKey($command);
    }
});
