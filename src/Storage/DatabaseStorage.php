<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Storage;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;

class DatabaseStorage implements SnapshotStorage
{
    /**
     * Save a snapshot with the given label.
     */
    public function save(string $label, array $data): array
    {
        // This method is called after ModelSnapshot is already created in Snapshot::createSnapshot
        // So we just return the data for consistency
        return $data;
    }

    /**
     * Load a snapshot by label.
     */
    public function load(string $label): ?array
    {
        $snapshot = ModelSnapshot::where('label', $label)->first();

        return $snapshot ? $snapshot->data : null;
    }

    /**
     * List all available snapshots.
     */
    public function list(): array
    {
        return ModelSnapshot::orderBy('created_at', 'desc')
            ->get()
            ->map(function (ModelSnapshot $snapshot): array {
                return [
                    'id' => $snapshot->id,
                    'label' => $snapshot->label,
                    'model_type' => $snapshot->model_type,
                    'model_id' => $snapshot->model_id,
                    'event_type' => $snapshot->event_type,
                    'created_at' => $snapshot->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Delete a snapshot by label.
     */
    public function delete(string $label): bool
    {
        return ModelSnapshot::where('label', $label)->delete() > 0;
    }

    /**
     * Clear all snapshots or snapshots for a specific model.
     */
    public function clear(?string $modelClass = null): int
    {
        $query = ModelSnapshot::query();

        if ($modelClass !== null && $modelClass !== '' && $modelClass !== '0') {
            $query->where('model_type', $modelClass);
        }

        return $query->delete();
    }
}
