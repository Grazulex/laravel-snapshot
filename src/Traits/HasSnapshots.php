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
     * Report of the last snapshot restore made with this model.
     *
     * @var array{snapshot_id: mixed, mode: string, dry_run: bool, restored: array<int, string>, ignored: array<string, string>}|null
     */
    protected ?array $lastSnapshotRestoreReport = null;

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

        $previousData = is_string($previousSnapshot->data) ? json_decode($previousSnapshot->data, true) : $previousSnapshot->data;

        return Snapshot::calculateDiff(
            Snapshot::decryptSnapshot((array) $previousData),
            Snapshot::decryptSnapshot($currentSnapshot)
        );
    }

    /**
     * Get the fields stored encrypted in this model's snapshots.
     *
     * Reads the optional $snapshotEncrypted property of the model.
     *
     * @return array<int, string>
     */
    public function getSnapshotEncryptedFields(): array
    {
        return property_exists($this, 'snapshotEncrypted') ? array_values((array) $this->snapshotEncrypted) : [];
    }

    /**
     * Get the fields masked in this model's snapshots.
     *
     * Reads the optional $snapshotMasked property of the model.
     *
     * @return array<int, string>
     */
    public function getSnapshotMaskedFields(): array
    {
        return property_exists($this, 'snapshotMasked') ? array_values((array) $this->snapshotMasked) : [];
    }

    /**
     * Restore this model to a previous snapshot state.
     *
     * Supported options:
     * - only: restore only these attributes
     * - except: restore every attribute but these
     * - dry_run: build the restore report without changing or saving the model
     *
     * The report of the last restore is available with getLastSnapshotRestoreReport().
     *
     * @param  array{only?: array<int, string>, except?: array<int, string>, dry_run?: bool}  $options
     */
    public function restoreFromSnapshot($snapshotId, array $options = []): bool
    {
        $options = $this->normalizeSnapshotRestoreOptions($options);
        $snapshot = $this->findSnapshotForRestore($snapshotId);

        $data = is_string($snapshot->data) ? json_decode($snapshot->data, true) : $snapshot->data;

        if (! is_array($data) || ! isset($data['attributes'])) {
            $this->lastSnapshotRestoreReport = null;

            return false;
        }

        [$attributes, $report] = $this->resolveSnapshotRestore($snapshot->id, $data, $options, false);

        $this->lastSnapshotRestoreReport = $report;

        if ($options['dry_run']) {
            return true;
        }

        $this->fill($attributes);

        return $this->save();
    }

    /**
     * Create a new model from a snapshot of this model, leaving this model untouched.
     *
     * The primary key and the timestamps of the snapshot are not copied. Accepts the
     * same options as restoreFromSnapshot(); with dry_run the copy is returned unsaved.
     *
     * @param  array{only?: array<int, string>, except?: array<int, string>, dry_run?: bool}  $options
     * @return static
     */
    public function restoreAsNew($snapshotId, array $options = [])
    {
        $options = $this->normalizeSnapshotRestoreOptions($options);
        $snapshot = $this->findSnapshotForRestore($snapshotId);

        $data = is_string($snapshot->data) ? json_decode($snapshot->data, true) : $snapshot->data;

        if (! is_array($data) || ! isset($data['attributes'])) {
            throw new InvalidArgumentException("Snapshot with ID or label {$snapshotId} has no attributes to restore");
        }

        [$attributes, $report] = $this->resolveSnapshotRestore($snapshot->id, $data, $options, true);

        /** @var static $copy */
        $copy = $this->newInstance();
        $copy->fill($attributes);

        if (! $options['dry_run']) {
            $copy->save();
        }

        $this->lastSnapshotRestoreReport = $report;
        $copy->lastSnapshotRestoreReport = $report;

        return $copy;
    }

    /**
     * Get the report of the last restoreFromSnapshot() or restoreAsNew() call on this model.
     *
     * Keys: snapshot_id, mode ('update' or 'copy'), dry_run, restored (names of the
     * attributes written) and ignored (attribute name => reason). Reasons are
     * 'filtered' (only/except), 'masked', 'primary_key', 'timestamp' and 'not_fillable'.
     *
     * @return array{snapshot_id: mixed, mode: string, dry_run: bool, restored: array<int, string>, ignored: array<string, string>}|null
     */
    public function getLastSnapshotRestoreReport(): ?array
    {
        return $this->lastSnapshotRestoreReport;
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

    /**
     * Find a snapshot of this model by ID or label.
     */
    private function findSnapshotForRestore($snapshotId): ModelSnapshot
    {
        $snapshot = $this->snapshots()->where(function ($query) use ($snapshotId): void {
            $query->where('id', $snapshotId)
                ->orWhere('label', $snapshotId);
        })->first();

        if (! $snapshot instanceof ModelSnapshot) {
            throw new InvalidArgumentException("Snapshot with ID or label {$snapshotId} not found");
        }

        return $snapshot;
    }

    /**
     * Validate and normalize the options of a snapshot restore.
     *
     * @return array{only: array<int, string>|null, except: array<int, string>, dry_run: bool}
     */
    private function normalizeSnapshotRestoreOptions(array $options): array
    {
        $unknown = array_diff(array_keys($options), ['only', 'except', 'dry_run']);

        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown restore option(s): '.implode(', ', $unknown));
        }

        foreach (['only', 'except'] as $key) {
            if (array_key_exists($key, $options) && ! is_array($options[$key])) {
                throw new InvalidArgumentException("The restore option '{$key}' must be an array of attribute names");
            }
        }

        return [
            'only' => array_key_exists('only', $options) ? array_values(array_map(strval(...), $options['only'])) : null,
            'except' => array_values(array_map(strval(...), $options['except'] ?? [])),
            'dry_run' => (bool) ($options['dry_run'] ?? false),
        ];
    }

    /**
     * Compute the attributes a restore writes and the report of the ignored ones.
     *
     * @param  array{only: array<int, string>|null, except: array<int, string>, dry_run: bool}  $options
     * @return array{0: array<string, mixed>, 1: array{snapshot_id: mixed, mode: string, dry_run: bool, restored: array<int, string>, ignored: array<string, string>}}
     */
    private function resolveSnapshotRestore($snapshotId, array $data, array $options, bool $asCopy): array
    {
        $stored = is_array($data['attributes']) ? $data['attributes'] : [];
        $ignored = [];

        foreach (array_keys($stored) as $field) {
            $field = (string) $field;

            if (($options['only'] !== null && ! in_array($field, $options['only'], true))
                || in_array($field, $options['except'], true)) {
                $ignored[$field] = 'filtered';
                unset($stored[$field]);
            }
        }

        $data['attributes'] = $stored;
        $restorable = Snapshot::restorableAttributes($data);

        foreach (array_keys(array_diff_key($stored, $restorable)) as $field) {
            $ignored[(string) $field] = 'masked';
        }

        $timestampColumns = $this->usesTimestamps()
            ? array_filter([$this->getCreatedAtColumn(), $this->getUpdatedAtColumn()])
            : [];

        $attributes = [];

        foreach ($restorable as $field => $value) {
            $field = (string) $field;

            if ($asCopy && $field === $this->getKeyName()) {
                $ignored[$field] = 'primary_key';
            } elseif ($asCopy && in_array($field, $timestampColumns, true)) {
                $ignored[$field] = 'timestamp';
            } elseif (! $this->isFillable($field)) {
                $ignored[$field] = 'not_fillable';
            } else {
                $attributes[$field] = $value;
            }
        }

        return [$attributes, [
            'snapshot_id' => $snapshotId,
            'mode' => $asCopy ? 'copy' : 'update',
            'dry_run' => $options['dry_run'],
            'restored' => array_map(strval(...), array_keys($attributes)),
            'ignored' => $ignored,
        ]];
    }
}
