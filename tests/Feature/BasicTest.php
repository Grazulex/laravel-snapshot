<?php

declare(strict_types=1);

it('passes basic assertions', function () {
    expect(true)->toBeTrue();
    expect(1 + 1)->toBe(2);
    expect('Laravel Snapshot')->toBeString();
});

it('has PHP requirements', function () {
    expect(PHP_VERSION_ID)->toBeGreaterThanOrEqual(80400); // PHP 8.4+
    expect(extension_loaded('json'))->toBeTrue();
    expect(function_exists('mb_strtoupper'))->toBeTrue();
});

it('can access composer autoload', function () {
    expect(class_exists('Composer\\Autoload\\ClassLoader'))->toBeTrue();
});

it('validates basic Laravel functionality', function () {
    expect(function_exists('config'))->toBeTrue();
    expect(function_exists('app'))->toBeTrue();
});
