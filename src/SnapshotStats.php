<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Illuminate\Database\Eloquent\Model;

class SnapshotStats
{
    private $model;

    private array $stats = [];

    public function __construct($model = null)
    {
        $this->model = $model;
    }

    /**
     * Static factory method.
     */
    public static function new($model = null): self
    {
        return new self($model);
    }

    /**
     * Get basic counters.
     */
    public function counters(): self
    {
        $query = ModelSnapshot::query();

        if ($this->model instanceof Model) {
            $query->where('model_type', get_class($this->model))
                ->where('model_id', $this->model->getKey());
        }

        $this->stats['total_snapshots'] = $query->count();
        $this->stats['snapshots_by_event'] = $query->groupBy('event_type')
            ->selectRaw('event_type, count(*) as count')
            ->pluck('count', 'event_type')
            ->toArray();

        return $this;
    }

    /**
     * Get most changed fields.
     */
    public function mostChangedFields(): self
    {
        $query = ModelSnapshot::query();

        if ($this->model instanceof Model) {
            $query->where('model_type', get_class($this->model))
                ->where('model_id', $this->model->getKey());
        }

        $fieldChanges = [];

        // Get all snapshots with metadata containing change information
        $snapshots = $query->whereNotNull('metadata')->get();

        foreach ($snapshots as $snapshot) {
            $metadata = $snapshot->metadata;

            if (isset($metadata['changed_fields']) && is_array($metadata['changed_fields'])) {
                foreach ($metadata['changed_fields'] as $field) {
                    $fieldChanges[$field] = ($fieldChanges[$field] ?? 0) + 1;
                }
            }
        }        // Sort by frequency (descending) and take top 10
        arsort($fieldChanges);
        $this->stats['most_changed_fields'] = array_slice($fieldChanges, 0, 10, true);

        return $this;
    }

    /**
     * Get change frequency statistics.
     */
    public function changeFrequency(): self
    {
        $query = ModelSnapshot::query();

        if ($this->model instanceof Model) {
            $query->where('model_type', get_class($this->model))
                ->where('model_id', $this->model->getKey());
        }

        // Changes by day (last 30 days) - Using date() which is supported by SQLite
        $this->stats['changes_by_day'] = $query->selectRaw('date(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->pluck('count', 'date')
            ->toArray();

        // Changes by week (last 12 weeks) - Using strftime for SQLite compatibility
        $this->stats['changes_by_week'] = $query->selectRaw('strftime("%Y-%W", created_at) as week, count(*) as count')
            ->groupBy('week')
            ->orderBy('week', 'desc')
            ->limit(12)
            ->pluck('count', 'week')
            ->toArray();

        // Changes by month (last 12 months) - Using strftime for SQLite compatibility
        $this->stats['changes_by_month'] = $query->selectRaw('strftime("%Y-%m", created_at) as month, count(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->pluck('count', 'month')
            ->toArray();

        // Average changes per day - Using simple calculation
        $totalSnapshots = $query->count();
        if ($totalSnapshots > 0) {
            $oldestSnapshot = $query->orderBy('created_at', 'asc')->first();
            $newestSnapshot = $query->orderBy('created_at', 'desc')->first();

            if ($oldestSnapshot && $newestSnapshot &&
                $oldestSnapshot->created_at && $newestSnapshot->created_at) {
                $diffInDays = max(1, $newestSnapshot->created_at->diffInDays($oldestSnapshot->created_at));
                $this->stats['average_changes_per_day'] = round($totalSnapshots / $diffInDays, 2);
            } else {
                $this->stats['average_changes_per_day'] = $totalSnapshots; // If same day, all changes on that day
            }
        } else {
            $this->stats['average_changes_per_day'] = 0;
        }

return $this;
    }

    /**
     * Get detailed event type statistics.
     */
    public function eventTypeAnalysis(): self
    {
        $baseQuery = ModelSnapshot::query();

        if ($this->model instanceof Model) {
            $baseQuery->where('model_type', get_class($this->model))
                ->where('model_id', $this->model->getKey());
        }

        // Count by event type
        $eventCounts = $baseQuery->groupBy('event_type')
            ->selectRaw('event_type, count(*) as count')
            ->pluck('count', 'event_type')
            ->toArray();

        $total = array_sum($eventCounts);

        // Calculate percentages
        $eventPercentages = [];
        foreach ($eventCounts as $eventType => $count) {
            $eventPercentages[$eventType] = $total > 0 ? round(($count / $total) * 100, 2) : 0;
        }

        $this->stats['event_type_counts'] = $eventCounts;
        $this->stats['event_type_percentages'] = $eventPercentages;

        // Most recent snapshots by type
        $recentByType = [];
        foreach (array_keys($eventCounts) as $eventType) {
            // Create a fresh query for each event type
            $recentQuery = ModelSnapshot::query();

            if ($this->model instanceof Model) {
                $recentQuery->where('model_type', get_class($this->model))
                    ->where('model_id', $this->model->getKey());
            }

            $recent = $recentQuery->where('event_type', $eventType)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recent && $recent->created_at) {
                $recentByType[$eventType] = $recent->created_at->toDateTimeString();
            }
        }

        $this->stats['most_recent_by_event_type'] = $recentByType;

        return $this;
    }

    /**
     * Get the compiled statistics.
     */
    public function get(): array
    {
        return $this->stats;
    }

    /**
     * Get statistics as JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->stats, JSON_PRETTY_PRINT);
    }
}
