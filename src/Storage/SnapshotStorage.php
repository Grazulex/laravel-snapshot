<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Storage;

interface SnapshotStorage
{
    /**
     * Save a snapshot with the given label.
     *
     * @param  string  $label  The snapshot label/identifier
     * @param  array  $data  The snapshot data to save
     * @return array The saved snapshot data
     */
    public function save(string $label, array $data): array;

    /**
     * Load a snapshot by label.
     *
     * @param  string  $label  The snapshot label/identifier
     * @return array|null The snapshot data or null if not found
     */
    public function load(string $label): ?array;

    /**
     * List all available snapshots.
     *
     * @return array Array of snapshots with their metadata
     */
    public function list(): array;

    /**
     * Delete a snapshot by label.
     *
     * @param  string  $label  The snapshot label/identifier
     * @return bool True if deleted, false if not found
     */
    public function delete(string $label): bool;

    /**
     * Clear all snapshots or snapshots for a specific model.
     *
     * @param  string|null  $modelClass  Optional model class to filter by
     * @return int Number of snapshots deleted
     */
    public function clear(?string $modelClass = null): int;
}
