<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Exception;
use Grazulex\LaravelSnapshot\Reports\SnapshotReport;
use Illuminate\Console\Command;

class GenerateReportCommand extends Command
{
    protected $signature = 'snapshot:report {model} {--id=} {--format=html} {--period=} {--output=}';

    protected $description = 'Generate a snapshot report for a model';

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $id = $this->option('id');
        $format = $this->option('format');
        $period = $this->option('period');
        $output = $this->option('output');

        // Validate model class
        if (! class_exists($modelClass)) {
            $this->error("Model class '{$modelClass}' not found.");

            return Command::FAILURE;
        }

        // Get model instance
        if ($id) {
            $model = $modelClass::find($id);
            if (! $model) {
                $this->error("Model {$modelClass} with ID {$id} not found.");

                return Command::FAILURE;
            }
        } else {
            $this->error('Model ID is required.');

            return Command::FAILURE;
        }

        try {
            $report = SnapshotReport::for($model)->format($format);

            if ($period) {
                $report->period($period);
            }

            $content = $report->generate();

            if ($output) {
                file_put_contents($output, $content);
                $this->info("Report saved to: {$output}");
            } else {
                $this->line($content);
            }

            $this->info("Report generated successfully for {$modelClass}#{$id}");

            return Command::SUCCESS;

        } catch (Exception $e) {
            $this->error("Failed to generate report: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
