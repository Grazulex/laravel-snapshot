<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Storage\DatabaseStorage;
use Grazulex\LaravelSnapshot\Storage\FileStorage;
use Grazulex\LaravelSnapshot\Storage\SnapshotStorage;
use Illuminate\Contracts\Encryption\DecryptException;
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

        return self::calculateDiff(self::decryptSnapshot($snapshotA), self::decryptSnapshot($snapshotB));
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

            $source = config('snapshot.serialization.include_hidden', false)
                ? (clone $model)->makeVisible($model->getHidden())
                : $model;

            $attributes = $source->toArray();

            // Remove excluded fields
            foreach ($excludeFields as $field) {
                unset($attributes[$field]);
            }

            $original = array_diff_key($model->getOriginal(), array_flip($excludeFields));

            [$encryptFields, $maskFields] = self::sensitiveFieldsFor($model);

            $masked = [];
            $encrypted = [];
            $mask = config('snapshot.sensitive.mask', '********');

            foreach ($maskFields as $field) {
                if (array_key_exists($field, $attributes)) {
                    $attributes[$field] = $mask;
                    $masked[$field] = true;
                }

                if (array_key_exists($field, $original)) {
                    $original[$field] = $mask;
                    $masked[$field] = true;
                }
            }

            foreach ($encryptFields as $field) {
                if (array_key_exists($field, $attributes)) {
                    $attributes[$field] = self::encryptValue($attributes[$field]);
                    $encrypted[$field] = true;
                }

                if (array_key_exists($field, $original)) {
                    $original[$field] = self::encryptValue($original[$field]);
                    $encrypted[$field] = true;
                }
            }

            $data = [
                'class' => get_class($model),
                'attributes' => $attributes,
                'original' => $original,
                'timestamp' => now()->toISOString(),
            ];

            if ($encrypted !== []) {
                $data['encrypted'] = array_keys($encrypted);
            }

            if ($masked !== []) {
                $data['masked'] = array_keys($masked);
            }

            return $data;
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
     * Decrypt the encrypted fields of a serialized snapshot.
     *
     * Masked fields keep their mask value. Values that cannot be decrypted
     * (for example after an application key rotation) are left untouched.
     */
    public static function decryptSnapshot(array $data): array
    {
        $fields = self::fieldList($data, 'encrypted');

        if ($fields === []) {
            return $data;
        }

        foreach (['attributes', 'original'] as $section) {
            if (! isset($data[$section]) || ! is_array($data[$section])) {
                continue;
            }

            foreach ($fields as $field) {
                if (! array_key_exists($field, $data[$section]) || ! is_string($data[$section][$field])) {
                    continue;
                }

                try {
                    $data[$section][$field] = self::decryptValue($data[$section][$field]);
                } catch (DecryptException) {
                    // Keep the stored value when it cannot be decrypted.
                }
            }
        }

        return $data;
    }

    /**
     * Get the attributes of a serialized snapshot that can be written back to a model.
     *
     * Encrypted fields are decrypted and masked fields are left out, so a
     * restore never overwrites a real value with its mask.
     *
     * @throws DecryptException When an encrypted field cannot be decrypted
     */
    public static function restorableAttributes(array $data): array
    {
        $attributes = isset($data['attributes']) && is_array($data['attributes']) ? $data['attributes'] : [];

        foreach (self::fieldList($data, 'masked') as $field) {
            unset($attributes[$field]);
        }

        foreach (self::fieldList($data, 'encrypted') as $field) {
            if (array_key_exists($field, $attributes) && is_string($attributes[$field])) {
                $attributes[$field] = self::decryptValue($attributes[$field]);
            }
        }

        return $attributes;
    }

    /**
     * Resolve the fields to encrypt and to mask for a model.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private static function sensitiveFieldsFor(Model $model): array
    {
        $configured = config('snapshot.sensitive.models', []);
        $modelConfig = is_array($configured) && isset($configured[get_class($model)]) && is_array($configured[get_class($model)])
            ? $configured[get_class($model)]
            : [];

        $encrypt = (array) ($modelConfig['encrypt'] ?? []);
        $mask = (array) ($modelConfig['mask'] ?? []);

        if (method_exists($model, 'getSnapshotEncryptedFields')) {
            $encrypt = array_merge($encrypt, (array) $model->getSnapshotEncryptedFields());
        }

        if (method_exists($model, 'getSnapshotMaskedFields')) {
            $mask = array_merge($mask, (array) $model->getSnapshotMaskedFields());
        }

        $mask = array_values(array_unique(array_map(strval(...), $mask)));

        // Masking wins over encryption when a field is declared in both lists.
        $encrypt = array_values(array_diff(array_unique(array_map(strval(...), $encrypt)), $mask));

        return [$encrypt, $mask];
    }

    /**
     * Read a list of field names stored in a serialized snapshot.
     *
     * @return array<int, string>
     */
    private static function fieldList(array $data, string $key): array
    {
        if (! isset($data[$key]) || ! is_array($data[$key])) {
            return [];
        }

        return array_values(array_map(strval(...), $data[$key]));
    }

    private static function encryptValue(mixed $value): string
    {
        return app('encrypter')->encryptString((string) json_encode($value));
    }

    private static function decryptValue(string $payload): mixed
    {
        return json_decode(app('encrypter')->decryptString($payload), true);
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
            $changedFields = self::detectChangedFields($model, $data);

            ModelSnapshot::create([
                'model_type' => get_class($model),
                'model_id' => $model->getKey(),
                'label' => $label,
                'event_type' => $eventType,
                'data' => $data,
                'metadata' => [
                    'created_by' => auth()->id() ?? null,
                    'ip_address' => config('snapshot.metadata.capture_ip_address', true) ? request()->ip() : null,
                    'user_agent' => config('snapshot.metadata.capture_user_agent', true) ? request()->userAgent() : null,
                    'changed_fields' => $changedFields,
                ],
            ]);
        }

        return $result;
    }

    /**
     * Determine which attributes changed since the previous snapshot of the same model.
     *
     * Returns an empty list when there is no previous snapshot to compare with.
     *
     * @return array<int, string>
     */
    private static function detectChangedFields(Model $model, array $data): array
    {
        $previous = ModelSnapshot::query()
            ->where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if (! $previous instanceof ModelSnapshot) {
            return [];
        }

        // Compare decrypted values: encryption is not deterministic.
        $previousData = self::decryptSnapshot((array) $previous->data);
        $data = self::decryptSnapshot($data);

        if (! isset($previousData['attributes'])
            || ! is_array($previousData['attributes'])
            || ! isset($data['attributes'])
            || ! is_array($data['attributes'])) {
            return [];
        }

        $diff = self::calculateDiff(
            ['attributes' => $previousData['attributes']],
            ['attributes' => $data['attributes']]
        );

        $fields = array_merge(
            array_keys($diff['added'] ?? []),
            array_keys($diff['modified'] ?? []),
            array_keys($diff['removed'] ?? [])
        );

        return array_values(array_unique(array_map(strval(...), $fields)));
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
