<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Storage;

class ArrayStorage implements StorageInterface
{
    private static array $snapshots = [];

    /**
     * Clear all snapshots (for testing).
     */
    public static function clearAll(): void
    {
        self::$snapshots = [];
    }

    /**
     * Save a snapshot with the given label.
     */
    public function save(string $label, array $data): array
    {
        $dataWithMeta = array_merge($data, [
            'label' => $label,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        self::$snapshots[$label] = $dataWithMeta;

        return $dataWithMeta;
    }

    /**
     * Load a snapshot by label.
     */
    public function load(string $label): ?array
    {
        return self::$snapshots[$label] ?? null;
    }

    /**
     * List all available snapshots.
     */
    public function list(): array
    {
        return array_map(function ($label, $data): array {
            return [
                'label' => $label,
                'created_at' => $data['timestamp'] ?? now()->toISOString(),
                'data' => $data,
            ];
        }, array_keys(self::$snapshots), self::$snapshots);
    }

    /**
     * Delete a snapshot by label.
     */
    public function delete(string $label): bool
    {
        if (isset(self::$snapshots[$label])) {
            unset(self::$snapshots[$label]);

            return true;
        }

        return false;
    }

    /**
     * Clear all snapshots or snapshots for a specific model.
     */
    public function clear(?string $modelClass = null): int
    {
        if ($modelClass === null) {
            $count = count(self::$snapshots);
            self::$snapshots = [];

            return $count;
        }

        $deleted = 0;
        foreach (self::$snapshots as $label => $data) {
            if (isset($data['class']) && $data['class'] === $modelClass) {
                unset(self::$snapshots[$label]);
                $deleted++;
            }
        }

        return $deleted;
    }
}
