<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Storage;

interface StorageInterface
{
    /**
     * Save snapshot data
     */
    public function save(string $label, array $data): array;

    /**
     * Load snapshot data by label
     */
    public function load(string $label): ?array;

    /**
     * List all snapshots
     */
    public function list(): array;

    /**
     * Delete snapshot by label
     */
    public function delete(string $label): bool;

    /**
     * Clear snapshots, optionally filtered by model class
     */
    public function clear(?string $modelClass = null): int;
}
