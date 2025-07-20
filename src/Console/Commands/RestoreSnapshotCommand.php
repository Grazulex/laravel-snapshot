<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RestoreSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'snapshot:restore 
                            {model : The model class (e.g., App\\Models\\User)}
                            {id : The model ID}
                            {snapshot : The snapshot ID or label to restore from}
                            {--dry-run : Show what would be restored without actually restoring}
                            {--force : Force restoration without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Restore a model to a previous snapshot state';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $modelId = $this->argument('id');
        $snapshotIdOrLabel = $this->argument('snapshot');

        // Validate model class
        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");

            return self::FAILURE;
        }

        // Check if model uses HasSnapshots trait
        $traits = class_uses_recursive($modelClass);
        if (! in_array(HasSnapshots::class, $traits)) {
            $this->error("Model {$modelClass} does not use the HasSnapshots trait.");

            return self::FAILURE;
        }

        // Find the model
        $model = $modelClass::find($modelId);
        if (! $model) {
            $this->error("Model {$modelClass} with ID {$modelId} not found.");

            return self::FAILURE;
        }

        // Find the snapshot
        $snapshot = ModelSnapshot::where('model_type', $modelClass)
            ->where('model_id', $modelId)
            ->where(function ($query) use ($snapshotIdOrLabel): void {
                $query->where('id', $snapshotIdOrLabel)
                    ->orWhere('label', $snapshotIdOrLabel);
            })
            ->first();

        if (! $snapshot) {
            $this->error("Snapshot with ID or label '{$snapshotIdOrLabel}' not found for this model.");

            return self::FAILURE;
        }

        $this->info("Found snapshot: {$snapshot->label} (created: {$snapshot->created_at->format('Y-m-d H:i:s')})");

        // Get snapshot data
        $snapshotData = $snapshot->data;

        if (! isset($snapshotData['attributes'])) {
            $this->error('Invalid snapshot data structure - no attributes found.');

            return self::FAILURE;
        }

        // Show current vs snapshot data
        $currentAttributes = $model->getAttributes();
        $snapshotAttributes = $snapshotData['attributes'];

        $changes = [];
        foreach ($snapshotAttributes as $key => $value) {
            $currentValue = $currentAttributes[$key] ?? null;
            if ($currentValue !== $value) {
                $changes[$key] = [
                    'current' => $currentValue,
                    'snapshot' => $value,
                ];
            }
        }

        if ($changes === []) {
            $this->info('Model is already in the same state as the snapshot.');

            return self::SUCCESS;
        }

        $this->info('The following changes will be made:');
        $this->table(
            ['Field', 'Current Value', 'Snapshot Value'],
            collect($changes)->map(fn ($change, $field): array => [
                $field,
                is_null($change['current']) ? 'NULL' : (string) $change['current'],
                is_null($change['snapshot']) ? 'NULL' : (string) $change['snapshot'],
            ])
        );

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - Model will not actually be restored');

            return self::SUCCESS;
        }

        // Confirm restoration unless --force is used
        if (! $this->option('force') && ! $this->confirm('Are you sure you want to restore the model to this snapshot?')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        // Perform restoration
        try {
            $success = $model->restoreFromSnapshot($snapshot->id);

            if ($success) {
                $this->info("Model successfully restored to snapshot '{$snapshot->label}'.");

                // Create a new snapshot to mark the restoration
                $model->snapshot("restored-from-{$snapshot->label}-".now()->format('Y-m-d-H-i-s'));
                $this->info('Created new snapshot marking this restoration.');
            } else {
                $this->error('Failed to restore model.');

                return self::FAILURE;
            }
        } catch (InvalidArgumentException $e) {
            $this->error("Error during restoration: {$e->getMessage()}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
