<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Illuminate\Console\Command;

class PruneSnapshotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'snapshot:prune
                            {--days= : Override the configured retention period (in days)}
                            {--model= : Only prune snapshots of this model class}
                            {--dry-run : Show how many snapshots would be deleted without deleting them}';

    /**
     * The console command description.
     */
    protected $description = 'Delete snapshots older than the configured retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysOption = $this->option('days');

        if ($daysOption === null && ! config('snapshot.retention.enabled', false)) {
            $this->info('Snapshot retention is disabled. Nothing to prune.');

            return self::SUCCESS;
        }

        $days = $this->parseDays($daysOption ?? config('snapshot.retention.days'));

        if ($days === null) {
            $this->error('The retention period must be a positive number of days.');

            return self::FAILURE;
        }

        $query = ModelSnapshot::query()->olderThanDays($days);

        if ($modelClass = $this->option('model')) {
            $query->forModel($modelClass);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info("No snapshots older than {$days} days found.");

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No snapshots will actually be deleted');
            $this->info("{$count} snapshots older than {$days} days would be deleted.");

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Pruned {$deleted} snapshots older than {$days} days.");

        return self::SUCCESS;
    }

    /**
     * Validate the retention period and return it as a positive integer.
     */
    private function parseDays(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $days = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $days === false ? null : $days;
    }
}
