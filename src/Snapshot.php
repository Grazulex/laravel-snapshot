<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Storage\DatabaseStorage;
use Grazulex\LaravelSnapshot\Storage\FileStorage;
use Grazulex\LaravelSnapshot\Storage\SnapshotStorage;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Snapshot
{
    private static ?SnapshotStorage $storage = null;

    /**
     * Save a manual snapshot with a given label.
     */
    public static function save($model, string $label): array
    {
        return self::createSnapshot($model, $label, 'manual');
    }

    /**
     * Save an automatic snapshot triggered by model events.
     */
    public static function auto($model, string $eventType): array
    {
        $label = self::generateAutoLabel($model, $eventType);

        return self::createSnapshot($model, $label, $eventType);
    }

    /**
     * Save a scheduled snapshot.
     */
    public static function scheduled($model, ?string $label = null): array
    {
        $finalLabel = $label ?? self::generateScheduledLabel($model, 'daily');

        return self::createSnapshot($model, $finalLabel, 'scheduled');
    }

    /**
     * Load a snapshot by label.
     */
    public static function load(string $label): ?array
    {
        return self::getStorage()->load($label);
    }

    /**
     * Compare two snapshots and return the differences.
     */
    public static function diff(string $labelA, string $labelB): array
    {
        $snapshotA = self::load($labelA);
        $snapshotB = self::load($labelB);

        if ($snapshotA === null) {
            throw new InvalidArgumentException("Snapshot '{$labelA}' not found");
        }

        if ($snapshotB === null) {
            throw new InvalidArgumentException("Snapshot '{$labelB}' not found");
        }

        return self::calculateDiff($snapshotA, $snapshotB);
    }

    /**
     * List all available snapshots.
     */
    public static function list(): array
    {
        return self::getStorage()->list();
    }

    /**
     * Delete a snapshot by label.
     */
    public static function delete(string $label): bool
    {
        return self::getStorage()->delete($label);
    }

    /**
     * Clear all snapshots or snapshots for a specific model.
     */
    public static function clear(?string $modelClass = null): int
    {
        return self::getStorage()->clear($modelClass);
    }

    /**
     * Get statistics for a model or all models.
     */
    public static function stats($model = null): SnapshotStats
    {
        return new SnapshotStats($model);
    }

    /**
     * Get timeline for a specific model.
     */
    public static function timeline($model, int $limit = 50): array
    {
        if (! $model instanceof Model) {
            throw new InvalidArgumentException('Model must be an Eloquent model instance');
        }

        return ModelSnapshot::where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Set a custom storage driver.
     */
    public static function setStorage(SnapshotStorage $storage): void
    {
        self::$storage = $storage;
    }

    /**
     * Serialize a model for storage (public for trait access).
     */
    public static function serializeModel($model): array
    {
        if ($model instanceof Model) {
            $excludeFields = config('snapshot.automatic.exclude_fields', []);
            $attributes = $model->toArray();

            // Remove excluded fields
            foreach ($excludeFields as $field) {
                unset($attributes[$field]);
            }

            return [
                'class' => get_class($model),
                'attributes' => $attributes,
                'original' => array_diff_key($model->getOriginal(), array_flip($excludeFields)),
                'timestamp' => now()->toISOString(),
            ];
        }

        if (is_array($model) || is_object($model)) {
            return [
                'class' => is_object($model) ? get_class($model) : 'array',
                'data' => json_decode(json_encode($model), true),
                'timestamp' => now()->toISOString(),
            ];
        }

        return [
            'class' => gettype($model),
            'data' => $model,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Calculate differences between two snapshots (public for trait access).
     */
    public static function calculateDiff(array $snapshotA, array $snapshotB): array
    {
        $diff = [];

        // Compare attributes if they exist
        $dataA = $snapshotA['attributes'] ?? $snapshotA['data'] ?? $snapshotA;
        $dataB = $snapshotB['attributes'] ?? $snapshotB['data'] ?? $snapshotB;

        // Find added keys
        foreach ($dataB as $key => $value) {
            if (! array_key_exists($key, $dataA)) {
                $diff['added'][$key] = $value;
            } elseif ($dataA[$key] !== $value) {
                $diff['modified'][$key] = [
                    'from' => $dataA[$key],
                    'to' => $value,
                ];
            }
        }

        // Find removed keys
        foreach ($dataA as $key => $value) {
            if (! array_key_exists($key, $dataB)) {
                $diff['removed'][$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Get the current storage instance.
     */
    private static function getStorage(): SnapshotStorage
    {
        if (! self::$storage instanceof SnapshotStorage) {
            $driver = config('snapshot.default', 'database');

            self::$storage = match ($driver) {
                'database' => new DatabaseStorage(),
                'file' => new FileStorage(),
                'array' => new ArrayStorage(),
                default => throw new InvalidArgumentException("Unsupported storage driver: {$driver}"),
            };
        }

        return self::$storage;
    }

    /**
     * Create a snapshot (internal method).
     */
    private static function createSnapshot($model, string $label, string $eventType): array
    {
        $data = self::serializeModel($model);
        $data['event_type'] = $eventType;
        $data['label'] = $label;

        // Save to storage
        $result = self::getStorage()->save($label, $data);

        // Also save to database if model is Eloquent
        if ($model instanceof Model) {
            ModelSnapshot::create([
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'label' => $label,
                'event_type' => $eventType,
                'data' => $data,
                'metadata' => [
                    'created_by' => auth()->id() ?? null,
                    'ip_address' => request()->ip() ?? null,
                    'user_agent' => request()->userAgent() ?? null,
                ],
            ]);
        }

        return $result;
    }

    /**
     * Generate automatic label for model events.
     */
    private static function generateAutoLabel($model, string $eventType): string
    {
        $modelClass = $model instanceof Model ? class_basename($model) : 'Model';
        $id = $model instanceof Model ? $model->getKey() : 'unknown';

        return "auto-{$modelClass}-{$id}-{$eventType}-".now()->format('Y-m-d-H-i-s');
    }

    /**
     * Generate scheduled label.
     */
    private static function generateScheduledLabel($model, string $frequency): string
    {
        $modelClass = $model instanceof Model ? class_basename($model) : 'Model';
        $id = $model instanceof Model ? $model->getKey() : 'unknown';

        return "scheduled-{$modelClass}-{$id}-{$frequency}-".now()->format('Y-m-d-H-i-s');
    }
}
