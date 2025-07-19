<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Exception;
use Grazulex\LaravelSnapshot\Snapshot;
use Illuminate\Console\Command;

class SaveSnapshotCommand extends Command
{
    protected $signature = 'snapshot:save {model} {--label=} {--id=}';

    protected $description = 'Save a snapshot of a model';

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $label = $this->option('label') ?? 'manual-'.now()->format('Y-m-d-H-i-s');
        $id = $this->option('id');

        // If ID is provided, load the specific model instance
        if ($id && class_exists($modelClass)) {
            $model = $modelClass::find($id);

            if (! $model) {
                $this->error("Model {$modelClass} with ID {$id} not found.");

                return Command::FAILURE;
            }
        } else {
            $this->error('Model ID is required for now.');

            return Command::FAILURE;
        }

        try {
            $result = Snapshot::save($model, $label);
            $this->info("Snapshot '{$label}' created successfully for {$modelClass}#{$id}");

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to create snapshot: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
