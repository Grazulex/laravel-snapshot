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
        // This would require analyzing the diff data stored in snapshots
        // For now, we'll return a placeholder
        $this->stats['most_changed_fields'] = [];

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

        $this->stats['changes_by_day'] = $query->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->pluck('count', 'date')
            ->toArray();

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
