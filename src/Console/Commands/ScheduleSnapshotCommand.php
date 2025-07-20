<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Exception;
use Grazulex\LaravelSnapshot\Snapshot;
use Illuminate\Console\Command;

class ScheduleSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'snapshot:schedule 
                            {model : The model class (e.g., App\\Models\\User)}
                            {--id= : Specific model ID (if not provided, snapshots all models of this type)}
                            {--label= : Custom label prefix for the snapshots}
                            {--limit= : Limit number of models to snapshot (default: 100)}';

    /**
     * The console command description.
     */
    protected $description = 'Create scheduled snapshots for models - designed to be run via cron';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $modelId = $this->option('id');
        $labelPrefix = $this->option('label') ?? 'scheduled';
        $limit = (int) ($this->option('limit') ?? 100);

        // Validate model class
        if (! class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");

            return self::FAILURE;
        }

        $timestamp = now()->format('Y-m-d-H-i');
        $created = 0;
        $errors = 0;

        try {
            if ($modelId) {
                // Snapshot specific model
                $model = $modelClass::find($modelId);

                if (! $model) {
                    $this->error("Model {$modelClass} with ID {$modelId} not found.");

                    return self::FAILURE;
                }

                $label = "{$labelPrefix}-{$timestamp}";
                Snapshot::scheduled($model, $label);
                $created = 1;

                $this->info("Created scheduled snapshot for {$modelClass}#{$modelId} with label: {$label}");
            } else {
                // Snapshot all models of this type (with limit)
                $models = $modelClass::limit($limit)->get();

                if ($models->isEmpty()) {
                    $this->info("No models of type {$modelClass} found.");

                    return self::SUCCESS;
                }

                $this->info("Creating scheduled snapshots for {$models->count()} models...");

                $progressBar = $this->output->createProgressBar($models->count());
                $progressBar->start();

                foreach ($models as $model) {
                    try {
                        $label = "{$labelPrefix}-{$timestamp}-{$model->getKey()}";
                        Snapshot::scheduled($model, $label);
                        $created++;
                    } catch (Exception $e) {
                        $errors++;
                        $this->newLine();
                        $this->error("Failed to create snapshot for {$modelClass}#{$model->getKey()}: {$e->getMessage()}");
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();
            }

            // Summary
            $this->info('Scheduled snapshots completed:');
            $this->info("Created: {$created} snapshots");

            if ($errors > 0) {
                $this->warn("Errors: {$errors} snapshots failed");

                return self::FAILURE;
            }

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("Fatal error during scheduled snapshot creation: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
