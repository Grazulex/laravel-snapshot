<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Illuminate\Console\Command;

class ClearSnapshotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'snapshot:clear 
                            {--model= : Clear snapshots for specific model class}
                            {--id= : Clear snapshots for specific model ID}
                            {--event= : Clear snapshots for specific event type}
                            {--before= : Clear snapshots created before this date (Y-m-d)}
                            {--after= : Clear snapshots created after this date (Y-m-d)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clear snapshots based on various criteria';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = ModelSnapshot::query();

        // Apply filters
        if ($modelClass = $this->option('model')) {
            $query->where('model_type', $modelClass);
            $this->info("Filtering by model: {$modelClass}");
        }

        if ($modelId = $this->option('id')) {
            $query->where('model_id', $modelId);
            $this->info("Filtering by model ID: {$modelId}");
        }

        if ($eventType = $this->option('event')) {
            $query->where('event_type', $eventType);
            $this->info("Filtering by event type: {$eventType}");
        }

        if ($beforeDate = $this->option('before')) {
            $query->where('created_at', '<', $beforeDate);
            $this->info("Filtering snapshots before: {$beforeDate}");
        }

        if ($afterDate = $this->option('after')) {
            $query->where('created_at', '>', $afterDate);
            $this->info("Filtering snapshots after: {$afterDate}");
        }

        // Count snapshots that would be affected
        $count = $query->count();

        if ($count === 0) {
            $this->info('No snapshots found matching the criteria.');

            return self::SUCCESS;
        }

        $this->info("Found {$count} snapshots matching the criteria.");

        // Show samples if dry-run
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No snapshots will actually be deleted');

            $samples = $query->limit(5)->get();
            $this->table(
                ['ID', 'Model', 'Label', 'Event', 'Created'],
                $samples->map(fn ($snapshot): array => [
                    $snapshot->id,
                    class_basename($snapshot->model_type)."#{$snapshot->model_id}",
                    $snapshot->label,
                    $snapshot->event_type,
                    $snapshot->created_at->format('Y-m-d H:i:s'),
                ])
            );

            return self::SUCCESS;
        }

        // Confirm deletion unless --force is used
        if (!$this->option('force') && ! $this->confirm("Are you sure you want to delete {$count} snapshots?")) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        // Perform deletion
        $deleted = $query->delete();

        $this->info("Successfully deleted {$deleted} snapshots.");

        return self::SUCCESS;
    }
}
