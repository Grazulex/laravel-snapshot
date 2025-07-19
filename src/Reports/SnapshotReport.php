<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Reports;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Grazulex\LaravelSnapshot\Support\TemplateRenderer;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class SnapshotReport
{
    private Model $model;

    private string $format = 'html';

    private array $options = [];

    private TemplateRenderer $renderer;

    private function __construct(Model $model)
    {
        $this->model = $model;
        $this->renderer = new TemplateRenderer();
    }

    /**
     * Create a report for a specific model.
     */
    public static function for(Model $model): self
    {
        return new self($model);
    }

    /**
     * Set the report format.
     */
    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set report options.
     */
    public function options(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Include diffs in the report.
     */
    public function withDiffs(): self
    {
        $this->options['include_diffs'] = true;

        return $this;
    }

    /**
     * Set time period for the report.
     */
    public function period(string $period): self
    {
        $this->options['period'] = $period;

        return $this;
    }

    /**
     * Generate the report.
     */
    public function generate(): string
    {
        $snapshots = $this->getSnapshots();

        return match ($this->format) {
            'html' => $this->generateHtmlReport($snapshots),
            'json' => $this->generateJsonReport($snapshots),
            'csv' => $this->generateCsvReport($snapshots),
            default => throw new InvalidArgumentException("Unsupported format: {$this->format}"),
        };
    }

    /**
     * Get snapshots for the model.
     */
    private function getSnapshots(): array
    {
        $query = ModelSnapshot::where('model_type', get_class($this->model))
            ->where('model_id', $this->model->getKey())
            ->orderBy('created_at', 'desc');

        // Apply period filter if specified
        if (isset($this->options['period'])) {
            $query = $this->applyPeriodFilter($query, $this->options['period']);
        }

        $limit = config('snapshot.reports.max_timeline_entries', 100);

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Apply period filter to query.
     */
    private function applyPeriodFilter($query, string $period)
    {
        return match ($period) {
            'today' => $query->whereDate('created_at', today()),
            'yesterday' => $query->whereDate('created_at', now()->subDay()->toDateString()),
            'last-week' => $query->where('created_at', '>=', now()->subWeek()),
            'last-month' => $query->where('created_at', '>=', now()->subMonth()),
            'last-year' => $query->where('created_at', '>=', now()->subYear()),
            default => $query,
        };
    }

    /**
     * Generate HTML report using templates.
     */
    private function generateHtmlReport(array $snapshots): string
    {
        if ($snapshots === []) {
            return $this->renderer->renderMultiple(
                [
                    'name' => 'report-template.html',
                    'replacements' => [
                        'MODEL_CLASS' => get_class($this->model),
                        'MODEL_ID' => $this->model->getKey(),
                        'GENERATED_AT' => now()->format('Y-m-d H:i:s'),
                        'TOTAL_SNAPSHOTS' => 0,
                    ],
                ],
                [
                    'SNAPSHOTS_CONTENT' => [
                        [
                            'name' => 'no-snapshots.html',
                            'replacements' => [],
                        ],
                    ],
                ]
            );
        }

        $snapshotItems = [];
        foreach ($snapshots as $snapshot) {
            $snapshotItems[] = [
                'name' => 'snapshot-item.html',
                'replacements' => [
                    'EVENT_CLASS' => "event-{$snapshot['event_type']}",
                    'SNAPSHOT_LABEL' => $snapshot['label'],
                    'EVENT_TYPE' => $snapshot['event_type'],
                    'CREATED_AT' => $snapshot['created_at'],
                    'DIFF_CONTENT' => $this->generateDiffContent(),
                ],
            ];
        }

        return $this->renderer->renderMultiple(
            [
                'name' => 'report-template.html',
                'replacements' => [
                    'MODEL_CLASS' => get_class($this->model),
                    'MODEL_ID' => $this->model->getKey(),
                    'GENERATED_AT' => now()->format('Y-m-d H:i:s'),
                    'TOTAL_SNAPSHOTS' => count($snapshots),
                ],
            ],
            [
                'SNAPSHOTS_CONTENT' => $snapshotItems,
            ]
        );
    }

    /**
     * Generate diff content for HTML report.
     */
    private function generateDiffContent(): string
    {
        if (! ($this->options['include_diffs'] ?? config('snapshot.reports.include_diffs', false))) {
            return '';
        }

        // This would generate diff information if available
        return '<div class="metadata-item"><strong>Changes:</strong> [Diff not implemented yet]</div>';
    }

    /**
     * Generate JSON report.
     */
    private function generateJsonReport(array $snapshots): string
    {
        return json_encode([
            'model' => [
                'class' => get_class($this->model),
                'id' => $this->model->getKey(),
            ],
            'generated_at' => now()->toISOString(),
            'total_snapshots' => count($snapshots),
            'snapshots' => $snapshots,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Generate CSV report.
     */
    private function generateCsvReport(array $snapshots): string
    {
        $csv = "Label,Event Type,Created At\n";

        foreach ($snapshots as $snapshot) {
            $csv .= "\"{$snapshot['label']}\",\"{$snapshot['event_type']}\",\"{$snapshot['created_at']}\"\n";
        }

        return $csv;
    }
}
