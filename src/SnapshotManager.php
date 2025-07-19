<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot;

use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Storage\StorageInterface;

class SnapshotManager
{
    protected StorageInterface $storage;

    public function __construct(?StorageInterface $storage = null)
    {
        $this->storage = $storage ?? new ArrayStorage();
    }

    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }

    public function setStorage(StorageInterface $storage): void
    {
        $this->storage = $storage;
    }

    public function save(string $label, array $data): array
    {
        return $this->storage->save($label, $data);
    }

    public function load(string $label): ?array
    {
        return $this->storage->load($label);
    }

    public function list(): array
    {
        return $this->storage->list();
    }

    public function delete(string $label): bool
    {
        return $this->storage->delete($label);
    }

    public function clear(?string $modelClass = null): int
    {
        return $this->storage->clear($modelClass);
    }
}
