<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Traits;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;
use Grazulex\LaravelSnapshot\Reports\SnapshotReport;
use Grazulex\LaravelSnapshot\Snapshot;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * @phpstan-ignore-next-line trait.unused
 */
trait HasSnapshots
{
    /**
     * Get all snapshots for this model.
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(ModelSnapshot::class, 'model_id')
            ->where('model_type', static::class)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Create a manual snapshot.
     */
    public function snapshot(?string $label = null): array
    {
        return Snapshot::save($this, $label ?? 'manual-'.now()->format('Y-m-d-H-i-s'));
    }

    /**
     * Get the snapshot timeline for this model.
     */
    public function getSnapshotTimeline(int $limit = 50): array
    {
        return $this->snapshots()
            ->limit($limit)
            ->get()
            ->map(function ($snapshot): array {
                return [
                    'id' => $snapshot->id,
                    'label' => $snapshot->label,
                    'event_type' => $snapshot->event_type,
                    'created_at' => $snapshot->created_at,
                    'data' => is_string($snapshot->data) ? json_decode($snapshot->data, true) : $snapshot->data,
                ];
            })
            ->toArray();
    }

    /**
     * Generate a history report for this model.
     */
    public function getHistoryReport(string $format = 'html'): string
    {
        return SnapshotReport::for($this)
            ->format($format)
            ->generate();
    }

    /**
     * Get the latest snapshot for this model.
     */
    public function getLatestSnapshot(): ?array
    {
        $snapshot = $this->snapshots()->first();

        return $snapshot ? (is_string($snapshot->data) ? json_decode($snapshot->data, true) : $snapshot->data) : null;
    }

    /**
     * Compare this model with a previous snapshot.
     */
    public function compareWithSnapshot(string $snapshotId): array
    {
        $currentSnapshot = Snapshot::serializeModel($this);

        // Try to find by ID first, then by label
        $previousSnapshot = $this->snapshots()->find($snapshotId)
            ?? $this->snapshots()->where('label', $snapshotId)->first();

        if (! $previousSnapshot) {
            throw new InvalidArgumentException("Snapshot with ID or label {$snapshotId} not found");
        }

        return Snapshot::calculateDiff(
            is_string($previousSnapshot->data) ? json_decode($previousSnapshot->data, true) : $previousSnapshot->data,
            $currentSnapshot
        );
    }

    /**
     * Restore this model to a previous snapshot state.
     */
    public function restoreFromSnapshot(string $snapshotId): bool
    {
        $snapshot = $this->snapshots()->find($snapshotId);

        if (! $snapshot) {
            throw new InvalidArgumentException("Snapshot with ID {$snapshotId} not found");
        }

        $data = is_string($snapshot->data) ? json_decode($snapshot->data, true) : $snapshot->data;

        if (isset($data['attributes'])) {
            $this->fill($data['attributes']);

            return $this->save();
        }

        return false;
    }

    /**
     * Generate a snapshot report for this model.
     */
    public function generateSnapshotReport(string $format = 'default'): SnapshotReport
    {
        return SnapshotReport::for($this);
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasSnapshots(): void
    {
        if (! config('snapshot.automatic.enabled')) {
            return;
        }

        $modelClass = static::class;
        $configuredModels = config('snapshot.automatic.models', []);

        // Check if this model is configured for automatic snapshots
        if (! array_key_exists($modelClass, $configuredModels)) {
            return;
        }

        $events = $configuredModels[$modelClass] ?? config('snapshot.automatic.events');

        if (in_array('created', $events)) {
            static::created(function ($model): void {
                Snapshot::auto($model, 'created');
            });
        }

        if (in_array('updated', $events)) {
            static::updated(function ($model): void {
                Snapshot::auto($model, 'updated');
            });
        }

        if (in_array('deleted', $events)) {
            static::deleted(function ($model): void {
                Snapshot::auto($model, 'deleted');
            });
        }
    }
}
