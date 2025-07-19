<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Console\Commands;

use Exception;
use Grazulex\LaravelSnapshot\Snapshot;
use Illuminate\Console\Command;

class DiffSnapshotCommand extends Command
{
    protected $signature = 'snapshot:diff {labelA} {labelB}';

    protected $description = 'Compare two snapshots and show differences';

    public function handle(): int
    {
        $labelA = $this->argument('labelA');
        $labelB = $this->argument('labelB');

        try {
            $diff = Snapshot::diff($labelA, $labelB);

            $this->info("Comparing snapshots: '{$labelA}' vs '{$labelB}'");
            $this->line('');

            if ($diff === []) {
                $this->info('No differences found between snapshots.');

                return Command::SUCCESS;
            }

            if (isset($diff['added']) && ! empty($diff['added'])) {
                $this->info('Added fields:');
                foreach ($diff['added'] as $field => $value) {
                    $this->line("  + {$field}: ".json_encode($value));
                }
                $this->line('');
            }

            if (isset($diff['modified']) && ! empty($diff['modified'])) {
                $this->info('Modified fields:');
                foreach ($diff['modified'] as $field => $change) {
                    $this->line("  ~ {$field}: ".json_encode($change['from']).' → '.json_encode($change['to']));
                }
                $this->line('');
            }

            if (isset($diff['removed']) && ! empty($diff['removed'])) {
                $this->info('Removed fields:');
                foreach ($diff['removed'] as $field => $value) {
                    $this->line("  - {$field}: ".json_encode($value));
                }
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error("Failed to compare snapshots: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
