# Advanced Usage

This guide covers advanced patterns, performance optimization, and sophisticated use cases for Laravel Snapshot.

## Table of Contents

- [Performance Optimization](#performance-optimization)
- [Custom Storage Drivers](#custom-storage-drivers)
- [Advanced Serialization](#advanced-serialization)
- [Event-Driven Architecture](#event-driven-architecture)
- [Multi-Tenant Applications](#multi-tenant-applications)
- [Data Migration Patterns](#data-migration-patterns)
- [Integration Patterns](#integration-patterns)
- [Security & Compliance](#security--compliance)

## Performance Optimization

### Database Optimization

#### Indexing Strategy

```sql
-- Essential indexes for snapshot table
CREATE INDEX idx_snapshots_model_timeline ON snapshots(model_type, model_id, created_at DESC);
CREATE INDEX idx_snapshots_event_type ON snapshots(event_type);
CREATE INDEX idx_snapshots_label_unique ON snapshots(label);
CREATE INDEX idx_snapshots_created_at ON snapshots(created_at);

-- For analytics queries
CREATE INDEX idx_snapshots_model_event ON snapshots(model_type, event_type);
CREATE INDEX idx_snapshots_daily_stats ON snapshots(DATE(created_at), event_type);
```

#### Query Optimization

```php
// Efficient timeline queries
$timeline = ModelSnapshot::where('model_type', User::class)
    ->where('model_id', $userId)
    ->select(['id', 'label', 'event_type', 'created_at']) // Only needed columns
    ->orderBy('created_at', 'desc')
    ->limit(50)
    ->get();

// Efficient statistics queries
$stats = ModelSnapshot::selectRaw('
        model_type,
        event_type, 
        COUNT(*) as count,
        DATE(created_at) as date
    ')
    ->where('created_at', '>=', now()->subDays(30))
    ->groupBy(['model_type', 'event_type', 'date'])
    ->get();
```

### Memory Optimization

#### Smart Field Exclusion

```php
// config/snapshot.php
'serialization' => [
    'include_relationships' => false,  // Skip relationships for performance
    'include_hidden' => false,        // Skip hidden fields
    'max_relationship_depth' => 1,    // Limit relationship depth
    'exclude_large_fields' => true,   // Skip fields over certain size
],

'automatic' => [
    'exclude_fields' => [
        // Exclude frequently changing, non-business fields
        'updated_at', 'last_seen_at', 'login_count',
        
        // Exclude large text fields
        'notes', 'description', 'content',
        
        // Exclude binary data
        'avatar', 'document', 'image_data',
    ],
],
```

#### Batch Processing

```php
// Process snapshots in batches for large datasets
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        if ($this->shouldSnapshot($user)) {
            Snapshot::save($user, "batch-{$user->id}");
        }
    }
});
```

### Storage Optimization

#### Compression for File Storage

```php
class CompressedFileStorage extends FileStorage
{
    public function save(string $label, array $data): array
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $compressed = gzcompress($json, 9);
        
        $filename = $this->path . '/' . $label . '.json.gz';
        file_put_contents($filename, $compressed);
        
        return $data;
    }
    
    public function load(string $label): ?array
    {
        $filename = $this->path . '/' . $label . '.json.gz';
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $compressed = file_get_contents($filename);
        $json = gzuncompress($compressed);
        
        return json_decode($json, true);
    }
}
```

#### Database Partitioning

```sql
-- Partition snapshots table by date for better performance
ALTER TABLE snapshots 
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202401 VALUES LESS THAN (202402),
    PARTITION p202402 VALUES LESS THAN (202403),
    PARTITION p202403 VALUES LESS THAN (202404),
    -- Add more partitions as needed
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

## Custom Storage Drivers

### Redis Storage Driver

```php
<?php

use Grazulex\LaravelSnapshot\Storage\SnapshotStorage;
use Illuminate\Support\Facades\Redis;

class RedisStorage implements SnapshotStorage
{
    private string $prefix;
    
    public function __construct(string $prefix = 'snapshot:')
    {
        $this->prefix = $prefix;
    }
    
    public function save(string $label, array $data): array
    {
        $key = $this->prefix . $label;
        $value = json_encode($data);
        
        Redis::set($key, $value);
        
        // Add to index for listing
        Redis::sadd($this->prefix . 'index', $label);
        
        // Set expiration if configured
        if ($ttl = config('snapshot.redis.ttl')) {
            Redis::expire($key, $ttl);
        }
        
        return $data;
    }
    
    public function load(string $label): ?array
    {
        $key = $this->prefix . $label;
        $value = Redis::get($key);
        
        return $value ? json_decode($value, true) : null;
    }
    
    public function list(): array
    {
        $labels = Redis::smembers($this->prefix . 'index');
        $snapshots = [];
        
        foreach ($labels as $label) {
            if ($snapshot = $this->load($label)) {
                $snapshots[$label] = $snapshot;
            }
        }
        
        return $snapshots;
    }
    
    public function delete(string $label): bool
    {
        $key = $this->prefix . $label;
        
        $deleted = Redis::del($key) > 0;
        
        if ($deleted) {
            Redis::srem($this->prefix . 'index', $label);
        }
        
        return $deleted;
    }
    
    public function clear(?string $modelClass = null): int
    {
        if ($modelClass === null) {
            // Clear all snapshots
            $labels = Redis::smembers($this->prefix . 'index');
            $count = 0;
            
            foreach ($labels as $label) {
                if ($this->delete($label)) {
                    $count++;
                }
            }
            
            return $count;
        }
        
        // Clear snapshots for specific model class
        $labels = Redis::smembers($this->prefix . 'index');
        $count = 0;
        
        foreach ($labels as $label) {
            $snapshot = $this->load($label);
            if ($snapshot && ($snapshot['class'] ?? null) === $modelClass) {
                if ($this->delete($label)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}
```

### S3 Storage Driver

```php
<?php

use Grazulex\LaravelSnapshot\Storage\SnapshotStorage;
use Illuminate\Support\Facades\Storage;

class S3Storage implements SnapshotStorage
{
    private string $disk;
    private string $path;
    
    public function __construct(string $disk = 's3', string $path = 'snapshots')
    {
        $this->disk = $disk;
        $this->path = $path;
    }
    
    public function save(string $label, array $data): array
    {
        $filename = $this->path . '/' . $label . '.json';
        $content = json_encode($data, JSON_PRETTY_PRINT);
        
        Storage::disk($this->disk)->put($filename, $content);
        
        return $data;
    }
    
    public function load(string $label): ?array
    {
        $filename = $this->path . '/' . $label . '.json';
        
        if (!Storage::disk($this->disk)->exists($filename)) {
            return null;
        }
        
        $content = Storage::disk($this->disk)->get($filename);
        
        return json_decode($content, true);
    }
    
    public function list(): array
    {
        $files = Storage::disk($this->disk)->files($this->path);
        $snapshots = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $label = basename($file, '.json');
                if ($snapshot = $this->load($label)) {
                    $snapshots[$label] = $snapshot;
                }
            }
        }
        
        return $snapshots;
    }
    
    public function delete(string $label): bool
    {
        $filename = $this->path . '/' . $label . '.json';
        
        if (Storage::disk($this->disk)->exists($filename)) {
            return Storage::disk($this->disk)->delete($filename);
        }
        
        return false;
    }
    
    public function clear(?string $modelClass = null): int
    {
        $files = Storage::disk($this->disk)->files($this->path);
        $count = 0;
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $label = basename($file, '.json');
                
                if ($modelClass === null) {
                    // Delete all
                    if (Storage::disk($this->disk)->delete($file)) {
                        $count++;
                    }
                } else {
                    // Delete specific model class
                    $snapshot = $this->load($label);
                    if ($snapshot && ($snapshot['class'] ?? null) === $modelClass) {
                        if (Storage::disk($this->disk)->delete($file)) {
                            $count++;
                        }
                    }
                }
            }
        }
        
        return $count;
    }
}
```

## Advanced Serialization

### Custom Serialization Rules

```php
class AdvancedModel extends Model
{
    use HasSnapshots;
    
    /**
     * Customize how this model is serialized for snapshots
     */
    public function toSnapshotArray(): array
    {
        $data = $this->toArray();
        
        // Add computed fields
        $data['computed_field'] = $this->computedValue;
        
        // Include specific relationships
        if ($this->relationLoaded('orders')) {
            $data['orders'] = $this->orders->map->only(['id', 'total', 'status']);
        }
        
        // Transform sensitive data
        if (isset($data['email'])) {
            $data['email_domain'] = substr($data['email'], strpos($data['email'], '@'));
            unset($data['email']); // Remove full email for privacy
        }
        
        // Add metadata
        $data['_metadata'] = [
            'serialized_at' => now()->toISOString(),
            'version' => '1.0',
            'includes_relationships' => $this->relationLoaded('orders'),
        ];
        
        return $data;
    }
}
```

### Custom Serializer Class

```php
<?php

namespace App\Services;

class AdvancedModelSerializer
{
    public function serialize($model): array
    {
        if (method_exists($model, 'toSnapshotArray')) {
            return $model->toSnapshotArray();
        }
        
        $data = [
            'class' => get_class($model),
            'attributes' => $this->serializeAttributes($model),
            'relationships' => $this->serializeRelationships($model),
            'metadata' => $this->generateMetadata($model),
            'timestamp' => now()->toISOString(),
        ];
        
        return $data;
    }
    
    private function serializeAttributes($model): array
    {
        $attributes = $model->getAttributes();
        $excludeFields = config('snapshot.automatic.exclude_fields', []);
        
        // Apply field exclusions
        foreach ($excludeFields as $field) {
            unset($attributes[$field]);
        }
        
        // Apply transformations
        foreach ($attributes as $key => $value) {
            $attributes[$key] = $this->transformValue($key, $value, $model);
        }
        
        return $attributes;
    }
    
    private function transformValue(string $key, $value, $model)
    {
        // Custom transformations based on field name or type
        if (str_contains($key, 'password')) {
            return '[REDACTED]';
        }
        
        if (str_contains($key, 'email') && config('snapshot.anonymize_email')) {
            return $this->anonymizeEmail($value);
        }
        
        if ($value instanceof \DateTime) {
            return $value->toISOString();
        }
        
        return $value;
    }
    
    private function anonymizeEmail(?string $email): ?string
    {
        if (!$email) return $email;
        
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        
        return substr($parts[0], 0, 2) . '***@' . $parts[1];
    }
}
```

## Event-Driven Architecture

### Custom Snapshot Events

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SnapshotCreated
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public $model,
        public string $label,
        public string $eventType,
        public array $snapshotData
    ) {}
}

class SnapshotCompared  
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public string $labelA,
        public string $labelB,
        public array $differences
    ) {}
}
```

### Event Listeners

```php
<?php

namespace App\Listeners;

use App\Events\SnapshotCreated;
use Illuminate\Support\Facades\Log;

class LogSnapshotActivity
{
    public function handle(SnapshotCreated $event): void
    {
        Log::info('Snapshot created', [
            'model' => get_class($event->model),
            'model_id' => $event->model->id ?? 'unknown',
            'label' => $event->label,
            'event_type' => $event->eventType,
        ]);
    }
}

class NotifyAdminOfCriticalChanges
{
    public function handle(SnapshotCreated $event): void
    {
        // Check if this is a critical model
        if ($this->isCriticalModel($event->model)) {
            // Send notification to administrators
            $this->notifyAdministrators($event);
        }
    }
    
    private function isCriticalModel($model): bool
    {
        return in_array(get_class($model), [
            'App\Models\Transaction',
            'App\Models\Payment',
            'App\Models\AdminUser',
        ]);
    }
}
```

### Enhanced Snapshot Class

```php
<?php

namespace App\Services;

use App\Events\SnapshotCreated;
use App\Events\SnapshotCompared;
use Grazulex\LaravelSnapshot\Snapshot as BaseSnapshot;

class EnhancedSnapshot extends BaseSnapshot
{
    public static function save($model, string $label, array $metadata = []): array
    {
        // Call parent save method
        $result = parent::save($model, $label);
        
        // Add custom metadata
        if (!empty($metadata)) {
            $result['custom_metadata'] = $metadata;
        }
        
        // Dispatch event
        event(new SnapshotCreated($model, $label, 'manual', $result));
        
        return $result;
    }
    
    public static function diff(string $labelA, string $labelB): array
    {
        $differences = parent::diff($labelA, $labelB);
        
        // Dispatch comparison event
        event(new SnapshotCompared($labelA, $labelB, $differences));
        
        return $differences;
    }
}
```

## Multi-Tenant Applications

### Tenant-Aware Snapshots

```php
class TenantAwareSnapshot extends Snapshot
{
    public static function save($model, string $label): array
    {
        // Add tenant context to label
        $tenantId = auth()->user()?->tenant_id ?? 'global';
        $tenantLabel = "tenant-{$tenantId}-{$label}";
        
        $result = parent::save($model, $tenantLabel);
        
        // Add tenant metadata
        $result['tenant_id'] = $tenantId;
        
        return $result;
    }
    
    public static function list(): array
    {
        $allSnapshots = parent::list();
        $tenantId = auth()->user()?->tenant_id;
        
        if (!$tenantId) {
            return $allSnapshots;
        }
        
        // Filter by tenant
        $tenantSnapshots = [];
        foreach ($allSnapshots as $label => $snapshot) {
            if (str_starts_with($label, "tenant-{$tenantId}-")) {
                // Remove tenant prefix for display
                $displayLabel = substr($label, strlen("tenant-{$tenantId}-"));
                $tenantSnapshots[$displayLabel] = $snapshot;
            }
        }
        
        return $tenantSnapshots;
    }
}
```

### Tenant-Specific Storage

```php
class TenantFileStorage extends FileStorage
{
    protected function getPath(): string
    {
        $tenantId = auth()->user()?->tenant_id ?? 'global';
        return $this->basePath . '/tenant-' . $tenantId;
    }
    
    public function save(string $label, array $data): array
    {
        $path = $this->getPath();
        
        // Ensure tenant directory exists
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        
        return parent::save($label, $data);
    }
}
```

## Data Migration Patterns

### Safe Migration with Snapshots

```php
class MigrateUserDataWithSnapshots
{
    public function migrate(): void
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                // Snapshot before migration
                Snapshot::save($user, "pre-migration-{$user->id}");
                
                try {
                    // Perform migration
                    $this->migrateUser($user);
                    
                    // Snapshot after migration
                    Snapshot::save($user->fresh(), "post-migration-{$user->id}");
                    
                } catch (Exception $e) {
                    // Log error and continue
                    Log::error("Migration failed for user {$user->id}", [
                        'error' => $e->getMessage(),
                        'user_id' => $user->id
                    ]);
                }
            }
        });
    }
    
    public function rollback(): void
    {
        $users = User::all();
        
        foreach ($users as $user) {
            $preSnapshot = Snapshot::load("pre-migration-{$user->id}");
            
            if ($preSnapshot) {
                // Restore from pre-migration snapshot
                $user->restoreFromSnapshot($preSnapshot);
                Log::info("Rolled back user {$user->id}");
            }
        }
    }
}
```

### A/B Testing with Snapshots

```php
class ABTestingService
{
    public function startTest(string $testName, $model): void
    {
        // Snapshot original state
        Snapshot::save($model, "ab-test-{$testName}-original-{$model->id}");
        
        // Apply test variant
        $this->applyTestVariant($testName, $model);
        
        // Snapshot test state
        Snapshot::save($model, "ab-test-{$testName}-variant-{$model->id}");
    }
    
    public function endTest(string $testName, $model, bool $keepVariant = false): void
    {
        if (!$keepVariant) {
            // Restore original state
            $originalSnapshot = Snapshot::load("ab-test-{$testName}-original-{$model->id}");
            if ($originalSnapshot) {
                $model->restoreFromSnapshot($originalSnapshot);
            }
        }
        
        // Generate test results
        $this->generateTestResults($testName, $model);
        
        // Clean up test snapshots
        Snapshot::delete("ab-test-{$testName}-original-{$model->id}");
        Snapshot::delete("ab-test-{$testName}-variant-{$model->id}");
    }
}
```

## Security & Compliance

### Encrypted Snapshots

```php
class EncryptedStorage implements SnapshotStorage
{
    private SnapshotStorage $baseStorage;
    
    public function __construct(SnapshotStorage $baseStorage)
    {
        $this->baseStorage = $baseStorage;
    }
    
    public function save(string $label, array $data): array
    {
        // Encrypt sensitive data
        $encryptedData = $this->encryptData($data);
        
        return $this->baseStorage->save($label, $encryptedData);
    }
    
    public function load(string $label): ?array
    {
        $data = $this->baseStorage->load($label);
        
        if ($data) {
            return $this->decryptData($data);
        }
        
        return null;
    }
    
    private function encryptData(array $data): array
    {
        $sensitiveFields = config('snapshot.encrypted_fields', []);
        
        foreach ($sensitiveFields as $field) {
            if (isset($data['attributes'][$field])) {
                $data['attributes'][$field] = encrypt($data['attributes'][$field]);
            }
        }
        
        return $data;
    }
    
    private function decryptData(array $data): array
    {
        $sensitiveFields = config('snapshot.encrypted_fields', []);
        
        foreach ($sensitiveFields as $field) {
            if (isset($data['attributes'][$field])) {
                try {
                    $data['attributes'][$field] = decrypt($data['attributes'][$field]);
                } catch (DecryptException $e) {
                    // Handle decryption failure
                    $data['attributes'][$field] = '[DECRYPTION_FAILED]';
                }
            }
        }
        
        return $data;
    }
}
```

### Audit Trail Integration

```php
class AuditableSnapshot extends Snapshot
{
    public static function save($model, string $label): array
    {
        $result = parent::save($model, $label);
        
        // Create audit trail entry
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'snapshot_created',
            'model_type' => get_class($model),
            'model_id' => $model->id ?? null,
            'snapshot_label' => $label,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
        
        return $result;
    }
}
```

## Next Steps

- [Troubleshooting Guide](troubleshooting.md) - Solve common issues
- [API Reference](api-reference.md) - Complete method documentation  
- [Examples](../examples/README.md) - See these patterns in action