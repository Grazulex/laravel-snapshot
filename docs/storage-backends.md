# Storage Backends

Laravel Snapshot supports multiple storage backends to fit different use cases and performance requirements.

## Overview

Storage backends determine where and how snapshot data is persisted. The package includes three built-in drivers:

- **Database Storage** (default) - Stores snapshots in database table
- **File Storage** - Stores snapshots as JSON files on disk
- **Array Storage** - Stores snapshots in memory (testing only)

## Database Storage

### Overview

The database storage driver saves snapshots in a dedicated `snapshots` table. This is the default and recommended option for most applications.

### Configuration

```php
// config/snapshot.php
'default' => 'database',

'drivers' => [
    'database' => [
        'driver' => 'database',
        'table' => 'snapshots',
    ],
],
```

### Database Schema

The snapshots table has the following structure:

```sql
CREATE TABLE snapshots (
    id BIGINT UNSIGNED PRIMARY KEY,
    model_type VARCHAR(255) NOT NULL,
    model_id VARCHAR(255) NOT NULL,
    label VARCHAR(255) NOT NULL UNIQUE,
    event_type VARCHAR(255) NOT NULL,
    data JSON NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_model (model_type, model_id),
    INDEX idx_event_type (event_type),
    INDEX idx_label (label),
    INDEX idx_created_at (created_at)
);
```

### Advantages

- **Fast Queries**: Indexed columns enable efficient searching and filtering
- **Relationships**: Can join with other tables and use Eloquent relationships  
- **Atomic Operations**: Database transactions ensure consistency
- **Built-in Backup**: Included in regular database backups
- **Scalable**: Works well with large datasets and high-volume applications
- **Analytics**: Easy to run analytics queries directly on snapshot data

### Disadvantages

- **Database Size**: Increases database size, may impact backup/restore times
- **Database Load**: Additional load on database server
- **Storage Cost**: May increase database hosting costs

### Best For

- Production applications
- Applications requiring frequent snapshot queries
- Applications needing complex filtering and analytics
- Multi-tenant applications

### Example Usage

```php
// Database storage is default - no special setup needed
Snapshot::save($user, 'user-snapshot');

// Query snapshots using Eloquent
$userSnapshots = ModelSnapshot::where('model_type', User::class)
    ->where('model_id', 1)
    ->orderBy('created_at', 'desc')
    ->get();
```

---

## File Storage

### Overview

File storage saves each snapshot as a separate JSON file on the filesystem. Each file is named using the snapshot label.

### Configuration

```php
// config/snapshot.php
'default' => 'file',

'drivers' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('snapshots'),
    ],
],
```

#### Environment Configuration

```env
SNAPSHOT_DRIVER=file
SNAPSHOT_FILE_PATH=/path/to/snapshots
```

### Directory Structure

```
storage/snapshots/
├── user-before-update.json
├── user-after-update.json
├── order-123-received.json
├── order-123-completed.json
└── auto-User-1-updated-2024-07-19-10-05-00.json
```

### File Format

Each snapshot file contains:

```json
{
    "label": "user-before-update",
    "event_type": "manual",
    "timestamp": "2024-07-19T10:00:00.000000Z",
    "class": "App\\Models\\User",
    "attributes": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-07-19T09:30:00.000000Z"
    },
    "original": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "metadata": {
        "created_by": 1,
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0..."
    }
}
```

### Advantages

- **Human Readable**: JSON files can be inspected directly
- **Easy Backup**: Simple file-based backup and restore
- **No Database Impact**: Doesn't affect database size or performance
- **Version Control**: Files can be tracked in version control systems
- **Portable**: Easy to move between environments
- **Storage Flexibility**: Can use different filesystems (local, S3, etc.)

### Disadvantages

- **Slower Queries**: No indexing, must scan files for filtering
- **File System Limits**: Limited by filesystem performance and limits
- **Concurrency**: Potential issues with concurrent file access
- **Memory Usage**: Large snapshots may consume more memory

### Best For

- Development and staging environments
- Applications with infrequent snapshot queries
- Archival storage scenarios
- Applications requiring easy manual inspection of snapshots

### Setup Instructions

1. **Create Storage Directory**:
```bash
mkdir -p storage/snapshots
chmod 755 storage/snapshots
```

2. **Set Permissions** (production):
```bash
chown -R www-data:www-data storage/snapshots
chmod 755 storage/snapshots
```

3. **Configure Laravel Storage** (optional):
```php
// config/filesystems.php
'disks' => [
    'snapshots' => [
        'driver' => 'local',
        'root' => storage_path('snapshots'),
    ],
],
```

### Example Usage

```php
// Switch to file storage
use Grazulex\LaravelSnapshot\Storage\FileStorage;

Snapshot::setStorage(new FileStorage(storage_path('snapshots')));

// Create snapshots - will be saved as files
Snapshot::save($user, 'user-snapshot');

// File created: storage/snapshots/user-snapshot.json
```

---

## Array Storage

### Overview

Array storage keeps snapshots in memory during the application lifecycle. Snapshots are lost when the application restarts.

### Configuration

```php
// config/snapshot.php
'default' => 'array',

'drivers' => [
    'array' => [
        'driver' => 'array',
    ],
],
```

### Advantages

- **Fastest Performance**: No I/O operations, pure memory access
- **No Persistence Overhead**: No database or file system operations
- **Clean Testing**: Automatically cleared between tests
- **Simple Setup**: No configuration or setup required

### Disadvantages

- **Non-Persistent**: Lost on application restart
- **Memory Usage**: Consumes application memory
- **Single Process**: Not shared between processes or servers

### Best For

- **Unit Testing**: Perfect for test environments
- **Development**: Quick prototyping and debugging
- **Temporary Operations**: Short-lived snapshot operations

### Example Usage

```php
// In tests or development
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;

// Set up array storage
Snapshot::setStorage(new ArrayStorage());

// Create snapshots - stored in memory
Snapshot::save($user, 'test-snapshot');

// Snapshots are automatically cleared when the application exits
```

### Testing Integration

```php
// In your test case
use Tests\TestCase;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Snapshot;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use array storage for all tests
        Snapshot::setStorage(new ArrayStorage());
    }
    
    public function test_user_snapshot()
    {
        $user = User::factory()->create();
        
        // Snapshot operations work normally but use memory
        Snapshot::save($user, 'test');
        $snapshot = Snapshot::load('test');
        
        $this->assertNotNull($snapshot);
    }
    
    // Snapshots are automatically cleared after each test
}
```

---

## Custom Storage Drivers

You can create custom storage drivers by implementing the `SnapshotStorage` interface.

### Creating a Custom Driver

```php
<?php

use Grazulex\LaravelSnapshot\Storage\SnapshotStorage;

class CustomStorage implements SnapshotStorage
{
    public function save(string $label, array $data): array
    {
        // Implement your save logic
        // Example: save to Redis, S3, etc.
        
        return $data;
    }
    
    public function load(string $label): ?array
    {
        // Implement your load logic
        // Return null if not found
        
        return $data ?? null;
    }
    
    public function list(): array
    {
        // Return array of all snapshots keyed by label
        
        return $snapshots;
    }
    
    public function delete(string $label): bool
    {
        // Delete snapshot by label
        // Return true if deleted, false if not found
        
        return $deleted;
    }
    
    public function clear(?string $modelClass = null): int
    {
        // Clear snapshots, optionally filtered by model class
        // Return count of deleted snapshots
        
        return $deletedCount;
    }
}
```

### Using Custom Storage

```php
// Use your custom storage
$customStorage = new CustomStorage($config);
Snapshot::setStorage($customStorage);
```

---

## Storage Comparison

| Feature | Database | File | Array |
|---------|----------|------|-------|
| **Persistence** | ✅ Permanent | ✅ Permanent | ❌ Memory only |
| **Performance** | 🔶 Fast | 🔶 Moderate | ✅ Fastest |
| **Querying** | ✅ Excellent | ❌ Limited | 🔶 Basic |
| **Backup** | ✅ Built-in | 🔶 Manual | ❌ N/A |
| **Scalability** | ✅ Excellent | 🔶 Moderate | ❌ Limited |
| **Setup Complexity** | 🔶 Moderate | ✅ Simple | ✅ None |
| **Memory Usage** | ✅ Low | ✅ Low | ❌ High |
| **Inspection** | 🔶 SQL tools | ✅ Direct | ❌ Debug only |

## Performance Considerations

### Database Storage Optimization

```php
// Optimize database queries
ModelSnapshot::where('model_type', User::class)
    ->where('created_at', '>=', now()->subDays(30))
    ->select(['id', 'label', 'created_at'])  // Select only needed columns
    ->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();

// Use indexes effectively
Schema::table('snapshots', function (Blueprint $table) {
    $table->index(['model_type', 'model_id', 'created_at']);
});
```

### File Storage Optimization

```php
// Configure file storage for better performance
'drivers' => [
    'file' => [
        'driver' => 'file',
        'path' => '/fast-ssd/snapshots',  // Use fast storage
        'compress' => true,               // Enable compression
        'cache_list' => true,             // Cache directory listings
    ],
],
```

### Memory Management

```php
// For large snapshots, exclude unnecessary data
'serialization' => [
    'include_relationships' => false,  // Skip relationships
    'include_timestamps' => false,     // Skip timestamps
    'max_string_length' => 1000,       // Truncate long strings
],
```

## Migration Between Storage Types

### Database to File

```php
// Manual migration script - run in php artisan tinker or custom command
use Grazulex\LaravelSnapshot\Storage\FileStorage;
use Grazulex\LaravelSnapshot\Storage\DatabaseStorage;
use Grazulex\LaravelSnapshot\Models\ModelSnapshot;

$fileStorage = new FileStorage(storage_path('snapshots'));

// Export all database snapshots to files
$snapshots = ModelSnapshot::all();
foreach ($snapshots as $snapshot) {
    $data = [
        'class' => $snapshot->model_type,
        'attributes' => $snapshot->data['attributes'] ?? $snapshot->data,
        'timestamp' => $snapshot->created_at->toISOString(),
        'event_type' => $snapshot->event_type,
        'metadata' => $snapshot->metadata,
    ];
    
    $fileStorage->save($snapshot->label, $data);
}

echo "Exported " . $snapshots->count() . " snapshots to files\n";
```

### File to Database

```php
// Manual migration script
use Grazulex\LaravelSnapshot\Storage\FileStorage;
use Grazulex\LaravelSnapshot\Snapshot;

// Change config to use database storage temporarily
config(['snapshot.default' => 'database']);

$fileStorage = new FileStorage(storage_path('snapshots'));
$snapshots = $fileStorage->list();

foreach ($snapshots as $label => $data) {
    // Reconstruct model if possible and save
    if (isset($data['class']) && class_exists($data['class'])) {
        $modelClass = $data['class'];
        // This is a simplified approach - you may need more complex logic
        // depending on your specific data structure
        Snapshot::save($data, $label);
    }
}

echo "Imported " . count($snapshots) . " snapshots to database\n";
```

### Manual Migration

```php
// Custom migration script
$fileStorage = new FileStorage(storage_path('snapshots'));
$dbStorage = new DatabaseStorage();

// Copy all snapshots
$snapshots = $fileStorage->list();
foreach ($snapshots as $label => $data) {
    $dbStorage->save($label, $data);
}
```

## Next Steps

- [Automatic Snapshots Configuration](automatic-snapshots.md)
- [Reports & Analytics](reports-analytics.md)  
- [Performance Optimization](advanced-usage.md#performance)