<?php

declare(strict_types=1);

it('can create basic configuration', function () {
    $config = include __DIR__.'/../../src/Config/snapshot.php';

    expect($config)
        ->toBeArray();
});

it('config path is accessible', function () {
    expect(file_exists(__DIR__.'/../../src/Config/snapshot.php'))
        ->toBeTrue();
});

it('config has expected structure', function () {
    $config = include __DIR__.'/../../src/Config/snapshot.php';

    expect($config)
        ->toBeArray()
        ->toHaveKey('default')
        ->toHaveKey('drivers')
        ->toHaveKey('serialization')
        ->toHaveKey('retention');
});
