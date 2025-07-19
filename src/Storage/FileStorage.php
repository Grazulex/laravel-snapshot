<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Storage;

class FileStorage implements StorageInterface
{
    private string $storagePath;

    public function __construct()
    {
        $this->storagePath = config('snapshot.drivers.file.path', storage_path('snapshots'));
        $this->ensureDirectoryExists();
    }

    /**
     * Save a snapshot with the given label.
     */
    public function save(string $label, array $data): array
    {
        $filePath = $this->getFilePath($label);
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }

    /**
     * Load a snapshot by label.
     */
    public function load(string $label): ?array
    {
        $filePath = $this->getFilePath($label);

        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        return json_decode($content, true);
    }

    /**
     * List all available snapshots.
     */
    public function list(): array
    {
        $files = glob($this->storagePath.'/*.json');
        $snapshots = [];

        foreach ($files as $file) {
            $label = basename($file, '.json');
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            $snapshots[] = [
                'label' => $label,
                'created_at' => $data['timestamp'] ?? filemtime($file),
                'file_path' => $file,
            ];
        }

        return $snapshots;
    }

    /**
     * Delete a snapshot by label.
     */
    public function delete(string $label): bool
    {
        $filePath = $this->getFilePath($label);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Clear all snapshots or snapshots for a specific model.
     */
    public function clear(?string $modelClass = null): int
    {
        $files = glob($this->storagePath.'/*.json');
        $deleted = 0;

        foreach ($files as $file) {
            if ($modelClass !== null && $modelClass !== '' && $modelClass !== '0') {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                if (isset($data['class']) && $data['class'] === $modelClass && unlink($file)) {
                    $deleted++;
                }
            } elseif (unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get the file path for a snapshot label.
     */
    private function getFilePath(string $label): string
    {
        return $this->storagePath.'/'.$label.'.json';
    }

    /**
     * Ensure the storage directory exists.
     */
    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
}
