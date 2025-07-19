<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Grazulex\LaravelSnapshot\SnapshotManager;
use Grazulex\LaravelSnapshot\Storage\StorageInterface;
use Illuminate\Console\Command;

class SnapshotListCommand extends Command
{
    protected $signature = 'snapshot:list {--model= : Filter by model class}';

    protected $description = 'List all snapshots';

    protected StorageInterface $storage;

    public function __construct()
    {
        parent::__construct();
        $this->storage = app(SnapshotManager::class)->getStorage();
    }

    public function setStorage(StorageInterface $storage): void
    {
        $this->storage = $storage;
    }

    public function handle(): int
    {
        $snapshots = $this->storage->list();
        $modelFilter = $this->option('model');

        if ($modelFilter) {
            $snapshots = array_filter($snapshots, fn ($snapshot): bool => isset($snapshot['model_type']) && $snapshot['model_type'] === $modelFilter
            );
        }

        if ($snapshots === []) {
            $this->info('No snapshots found.');

            return 0;
        }

        $this->table(
            ['ID', 'Label', 'Model', 'Type', 'Created'],
            array_map(fn ($snapshot): array => [
                $snapshot['id'] ?? 'N/A',
                $snapshot['label'] ?? 'N/A',
                $snapshot['model_type'] ?? 'N/A',
                $snapshot['event_type'] ?? 'N/A',
                $snapshot['created_at'] ?? 'N/A',
            ], $snapshots)
        );

        return 0;
    }
}
