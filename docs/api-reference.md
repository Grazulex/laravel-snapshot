# API Reference

This document provides a comprehensive reference for all Laravel Snapshot classes and methods.

## Core Classes

### Snapshot

The main static class for creating, loading, and comparing snapshots.

#### Methods

##### `save($model, string $label): array`

Create a manual snapshot with a given label.

**Parameters:**
- `$model` - The model, array, object, or primitive to snapshot
- `$label` - Unique label for the snapshot

**Returns:** Array containing the saved snapshot data

**Example:**
```php
$snapshot = Snapshot::save($user, 'user-before-update');
```

##### `auto($model, string $eventType): array`

Create an automatic snapshot triggered by model events.

**Parameters:**
- `$model` - The model instance
- `$eventType` - Event type ('created', 'updated', 'deleted')

**Returns:** Array containing the saved snapshot data

**Example:**
```php
$snapshot = Snapshot::auto($user, 'updated');
```

##### `scheduled($model, string $frequency): array`

Create a scheduled snapshot.

**Parameters:**
- `$model` - The model instance  
- `$frequency` - Schedule frequency ('daily', 'hourly', 'weekly')

**Returns:** Array containing the saved snapshot data

**Example:**
```php
$snapshot = Snapshot::scheduled($user, 'daily');
```

##### `load(string $label): ?array`

Load a snapshot by its label.

**Parameters:**
- `$label` - The snapshot label

**Returns:** Snapshot data array or null if not found

**Example:**
```php
$snapshot = Snapshot::load('user-before-update');
```

##### `diff(string $labelA, string $labelB): array`

Compare two snapshots and return differences.

**Parameters:**
- `$labelA` - First snapshot label
- `$labelB` - Second snapshot label

**Returns:** Array with 'added', 'modified', 'removed' keys

**Example:**
```php
$diff = Snapshot::diff('user-before', 'user-after');
/*
[
    'modified' => [
        'name' => ['from' => 'Old Name', 'to' => 'New Name']
    ],
    'added' => [],
    'removed' => []
]
*/
```

**Throws:** `InvalidArgumentException` if snapshot not found

##### `list(): array`

List all available snapshots.

**Returns:** Array of snapshots keyed by label

**Example:**
```php
$snapshots = Snapshot::list();
foreach ($snapshots as $label => $snapshot) {
    echo "{$label}: {$snapshot['timestamp']}\n";
}
```

##### `delete(string $label): bool`

Delete a snapshot by label.

**Parameters:**
- `$label` - The snapshot label

**Returns:** True if deleted, false if not found

**Example:**
```php
$deleted = Snapshot::delete('old-snapshot');
```

##### `clear(?string $modelClass = null): int`

Clear snapshots for a specific model class or all snapshots.

**Parameters:**
- `$modelClass` - Optional model class name to filter by

**Returns:** Number of snapshots deleted

**Example:**
```php
$count = Snapshot::clear('App\Models\User'); // Clear User snapshots
$count = Snapshot::clear(); // Clear all snapshots
```

##### `stats($model = null): SnapshotStats`

Get statistics for a model or all models.

**Parameters:**
- `$model` - Optional model instance or null for global stats

**Returns:** SnapshotStats instance for chaining

**Example:**
```php
$stats = Snapshot::stats($user)
    ->counters()
    ->changeFrequency()
    ->get();
```

##### `timeline($model, int $limit = 50): array`

Get timeline for a specific model.

**Parameters:**
- `$model` - Model instance
- `$limit` - Maximum number of entries (default: 50)

**Returns:** Array of timeline entries

**Example:**
```php
$timeline = Snapshot::timeline($user, 20);
```

##### `setStorage(SnapshotStorage $storage): void`

Set a custom storage driver.

**Parameters:**
- `$storage` - Storage driver instance

**Example:**
```php
Snapshot::setStorage(new ArrayStorage());
```

##### `serializeModel($model): array`

Serialize a model for storage (public for trait access).

**Parameters:**
- `$model` - The model to serialize

**Returns:** Array representation of the model

**Example:**
```php
$serialized = Snapshot::serializeModel($user);
```

##### `calculateDiff(array $snapshotA, array $snapshotB): array`

Calculate differences between two snapshots (public for trait access).

**Parameters:**
- `$snapshotA` - First snapshot data
- `$snapshotB` - Second snapshot data

**Returns:** Difference array with 'added', 'modified', 'removed' keys

---

### SnapshotStats

Class for generating snapshot statistics and analytics.

#### Methods

##### `counters(): self`

Get basic counters (total snapshots, snapshots by event type).

**Returns:** Self for method chaining

**Example:**
```php
$stats = Snapshot::stats($user)->counters()->get();
/*
[
    'total_snapshots' => 15,
    'snapshots_by_event' => [
        'manual' => 5,
        'created' => 1,
        'updated' => 9
    ]
]
*/
```

##### `mostChangedFields(): self`

Get statistics about most frequently changed fields.

**Returns:** Self for method chaining

##### `changeFrequency(): self`

Get change frequency statistics by day.

**Returns:** Self for method chaining

**Example:**
```php
$stats = Snapshot::stats($user)->changeFrequency()->get();
/*
[
    'changes_by_day' => [
        '2024-07-19' => 3,
        '2024-07-18' => 2,
        '2024-07-17' => 5
    ]
]
*/
```

##### `get(): array`

Get the compiled statistics.

**Returns:** Array of statistics

##### `toJson(): string`

Get statistics as JSON string.

**Returns:** JSON-encoded statistics

---

## Traits

### HasSnapshots

Trait that can be added to Eloquent models to provide convenient snapshot methods.

#### Methods

##### `snapshots(): HasMany`

Get all snapshots for this model as a relationship.

**Returns:** Laravel HasMany relationship

**Example:**
```php
$user = User::find(1);
$snapshots = $user->snapshots; // Collection of ModelSnapshot instances
```

##### `snapshot(?string $label = null): array`

Create a manual snapshot of this model.

**Parameters:**
- `$label` - Optional custom label (auto-generated if null)

**Returns:** Snapshot data array

**Example:**
```php
$snapshot = $user->snapshot('before-important-update');
```

##### `getSnapshotTimeline(int $limit = 50): array`

Get timeline of snapshots for this model.

**Parameters:**
- `$limit` - Maximum number of entries (default: 50)

**Returns:** Array of timeline entries

**Example:**
```php
$timeline = $user->getSnapshotTimeline(10);
```

##### `getHistoryReport(string $format = 'html'): string`

Generate a history report for this model.

**Parameters:**
- `$format` - Report format ('html', 'json', 'csv')

**Returns:** Report as string

**Example:**
```php
$htmlReport = $user->getHistoryReport('html');
$jsonReport = $user->getHistoryReport('json');
```

##### `getLatestSnapshot(): ?array`

Get the latest snapshot for this model.

**Returns:** Latest snapshot data or null

**Example:**
```php
$latest = $user->getLatestSnapshot();
```

##### `compareWithSnapshot(string $snapshotId): array`

Compare current model state with a previous snapshot.

**Parameters:**
- `$snapshotId` - Database ID of the snapshot to compare with

**Returns:** Difference array

**Example:**
```php
$diff = $user->compareWithSnapshot('123');
```

**Throws:** `InvalidArgumentException` if snapshot not found

##### `restoreFromSnapshot(string $snapshotId): bool`

Restore this model to a previous snapshot state.

**Parameters:**
- `$snapshotId` - Database ID of the snapshot to restore from

**Returns:** True if successfully restored, false otherwise

**Example:**
```php
$restored = $user->restoreFromSnapshot('123');
```

**Throws:** `InvalidArgumentException` if snapshot not found

#### Automatic Snapshots

The trait automatically sets up model event listeners based on configuration:

```php
protected static function bootHasSnapshots(): void
{
    // Automatically called when model uses the trait
    // Sets up created, updated, deleted event listeners
}
```

---

## Models

### ModelSnapshot

Eloquent model representing snapshots stored in the database.

#### Properties

- `$table = 'snapshots'`
- `$fillable = ['model_type', 'model_id', 'label', 'event_type', 'data', 'metadata']`
- `$casts = ['data' => 'array', 'metadata' => 'array']`

#### Methods

##### `snapshotable(): MorphTo`

Get the owning model that was snapshot.

**Returns:** Laravel MorphTo relationship

##### `scopeForModel($query, string $modelType)`

Scope to filter snapshots by model type.

**Parameters:**
- `$query` - Query builder
- `$modelType` - Model class name

##### `scopeForEvent($query, string $eventType)`

Scope to filter snapshots by event type.

**Parameters:**
- `$query` - Query builder  
- `$eventType` - Event type ('manual', 'created', 'updated', 'deleted', 'scheduled')

##### `scopeWithLabel($query, string $label)`

Scope to filter snapshots by label.

**Parameters:**
- `$query` - Query builder
- `$label` - Snapshot label

**Example:**
```php
$manualSnapshots = ModelSnapshot::forEvent('manual')->get();
$userSnapshots = ModelSnapshot::forModel('App\Models\User')->get();
$specificSnapshot = ModelSnapshot::withLabel('user-before-update')->first();
```

---

## Storage Interfaces

### SnapshotStorage

Abstract base class for storage drivers.

#### Abstract Methods

##### `save(string $label, array $data): array`

Save a snapshot with the given label.

##### `load(string $label): ?array`

Load a snapshot by label.

##### `list(): array`

List all snapshots.

##### `delete(string $label): bool`

Delete a snapshot by label.

##### `clear(?string $modelClass = null): int`

Clear snapshots, optionally filtered by model class.

---

### Storage Implementations

#### DatabaseStorage

Stores snapshots in the `snapshots` database table.

- **Pros**: Fast queries, relationships, indexing
- **Cons**: Increases database size
- **Best for**: Production applications

#### FileStorage

Stores snapshots as JSON files on disk.

- **Pros**: Human-readable, easy backup, no DB impact
- **Cons**: Slower for large datasets
- **Best for**: Archival, development

**Configuration:**
```php
'drivers' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('snapshots'),
    ],
],
```

#### ArrayStorage

Stores snapshots in memory (lost on restart).

- **Pros**: Very fast, no persistence overhead
- **Cons**: Not persistent
- **Best for**: Testing only

---

## Exception Handling

### InvalidArgumentException

Thrown when:
- Snapshot label not found during diff or load operations
- Invalid model provided to restoration methods
- Invalid configuration values

**Example:**
```php
try {
    $diff = Snapshot::diff('non-existent-1', 'non-existent-2');
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
    // Error: Snapshot 'non-existent-1' not found
}
```

### Storage Exceptions

Various exceptions can be thrown by storage drivers:
- File permission errors (FileStorage)
- Database connection errors (DatabaseStorage)  
- Disk space issues

**Example:**
```php
try {
    Snapshot::save($model, 'test');
} catch (Exception $e) {
    Log::error('Snapshot failed', ['error' => $e->getMessage()]);
}
```

---

## Configuration Reference

See the [Configuration Guide](configuration.md) for detailed configuration options.

### Key Configuration Arrays

```php
'drivers' => [...],        // Storage driver configurations
'automatic' => [...],      // Automatic snapshot settings
'scheduled' => [...],      // Scheduled snapshot settings
'serialization' => [...],  // Model serialization options
'retention' => [...],      // Data retention policies
'reports' => [...],        // Report generation settings
```

## Next Steps

- [Console Commands Reference](console-commands.md)
- [Storage Backends Guide](storage-backends.md)
- [Advanced Usage Examples](advanced-usage.md)