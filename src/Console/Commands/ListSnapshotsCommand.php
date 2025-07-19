<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Exception;
use Grazulex\LaravelSnapshot\Snapshot;
use Illuminate\Console\Command;

class ListSnapshotsCommand extends Command
{
    protected $signature = 'snapshot:list {--model=} {--limit=50}';

    protected $description = 'List all available snapshots';

    public function handle(): int
    {
        try {
            $snapshots = Snapshot::list();
            $modelFilter = $this->option('model');
            $limit = (int) $this->option('limit');

            if ($modelFilter) {
                $snapshots = array_filter($snapshots, function ($snapshot) use ($modelFilter): bool {
                    return isset($snapshot['model_type']) && $snapshot['model_type'] === $modelFilter;
                });
            }

            $snapshots = array_slice($snapshots, 0, $limit);

            if ($snapshots === []) {
                $this->info('No snapshots found.');

                return Command::SUCCESS;
            }

            $headers = ['Label', 'Model', 'Event Type', 'Created At'];
            $rows = [];

            foreach ($snapshots as $snapshot) {
                $rows[] = [
                    $snapshot['label'] ?? 'N/A',
                    ($snapshot['model_type'] ?? 'N/A').'#'.($snapshot['model_id'] ?? 'N/A'),
                    $snapshot['event_type'] ?? 'N/A',
                    $snapshot['created_at'] ?? 'N/A',
                ];
            }

            $this->table($headers, $rows);
            $this->info('Total snapshots: '.count($snapshots));

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to list snapshots: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
