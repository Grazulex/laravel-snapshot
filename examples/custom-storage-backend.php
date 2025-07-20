<?php

declare(strict_types=1);
/**
 * Example: Custom Storage Backend
 * Description: Implement and demonstrate a custom storage driver for Laravel Snapshot
 *
 * Prerequisites:
 * - Laravel Snapshot package configured
 * - Understanding of StorageInterface
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/custom-storage-backend.php';
 */

use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Storage\StorageInterface;

/**
 * Custom Redis Storage Backend Example
 * 
 * This example demonstrates how to create a custom storage backend
 * that stores snapshots in Redis with TTL support
 */
class RedisStorage implements StorageInterface
{
    private array $data = []; // Mock Redis data store
    private array $ttl = [];  // Mock TTL tracking
    private int $defaultTtl;
    
    public function __construct(int $defaultTtl = 3600)
    {
        $this->defaultTtl = $defaultTtl;
        echo "   Redis Storage initialized with TTL: {$defaultTtl} seconds\n";
    }
    
    public function save(string $label, array $data): array
    {
        // Add metadata
        $snapshot = [
            'label' => $label,
            'data' => $data,
            'stored_at' => time(),
            'storage_type' => 'redis',
            'compressed' => false,
        ];
        
        // Compress large snapshots
        $serialized = json_encode($snapshot);
        if (strlen($serialized) > 1024) {
            $snapshot['data'] = base64_encode(gzcompress(json_encode($data)));
            $snapshot['compressed'] = true;
        }
        
        // Store in mock Redis
        $this->data[$label] = $snapshot;
        $this->ttl[$label] = time() + $this->defaultTtl;
        
        echo "   ✓ Stored snapshot '{$label}' in Redis (TTL: {$this->defaultTtl}s)\n";
        
        return $snapshot;
    }
    
    public function load(string $label): ?array
    {
        // Check TTL
        if (isset($this->ttl[$label]) && time() > $this->ttl[$label]) {
            unset($this->data[$label], $this->ttl[$label]);
            echo "   ⚠ Snapshot '{$label}' expired and was removed\n";
            return null;
        }
        
        if (!isset($this->data[$label])) {
            return null;
        }
        
        $snapshot = $this->data[$label];
        
        // Decompress if needed
        if ($snapshot['compressed']) {
            $decompressed = json_decode(gzuncompress(base64_decode($snapshot['data'])), true);
            $snapshot['data'] = $decompressed;
            $snapshot['compressed'] = false;
        }
        
        echo "   ✓ Loaded snapshot '{$label}' from Redis\n";
        
        return $snapshot;
    }
    
    public function list(): array
    {
        // Clean up expired snapshots
        $now = time();
        foreach ($this->ttl as $label => $expires) {
            if ($now > $expires) {
                unset($this->data[$label], $this->ttl[$label]);
            }
        }
        
        $result = [];
        foreach ($this->data as $label => $snapshot) {
            $result[$label] = [
                'timestamp' => date('Y-m-d H:i:s', $snapshot['stored_at']),
                'ttl_remaining' => $this->ttl[$label] - $now,
                'storage_type' => 'redis',
                'compressed' => $snapshot['compressed'],
                'data' => $snapshot['data'],
            ];
        }
        
        return $result;
    }
    
    public function delete(string $label): bool
    {
        if (isset($this->data[$label])) {
            unset($this->data[$label], $this->ttl[$label]);
            echo "   ✓ Deleted snapshot '{$label}' from Redis\n";
            return true;
        }
        
        return false;
    }
    
    public function clear(?string $modelClass = null): int
    {
        $count = 0;
        $toDelete = [];
        
        foreach ($this->data as $label => $snapshot) {
            if ($modelClass === null) {
                $toDelete[] = $label;
            } else {
                $data = $snapshot['data'];
                if (isset($data['class']) && $data['class'] === $modelClass) {
                    $toDelete[] = $label;
                }
            }
        }
        
        foreach ($toDelete as $label) {
            unset($this->data[$label], $this->ttl[$label]);
            $count++;
        }
        
        echo "   ✓ Cleared {$count} snapshots from Redis\n";
        
        return $count;
    }
    
    public function getStats(): array
    {
        $totalSize = 0;
        $compressed = 0;
        $expired = 0;
        $now = time();
        
        foreach ($this->data as $label => $snapshot) {
            $totalSize += strlen(json_encode($snapshot));
            if ($snapshot['compressed']) {
                $compressed++;
            }
            if (isset($this->ttl[$label]) && $now > $this->ttl[$label]) {
                $expired++;
            }
        }
        
        return [
            'total_snapshots' => count($this->data),
            'total_size_bytes' => $totalSize,
            'compressed_snapshots' => $compressed,
            'expired_snapshots' => $expired,
            'storage_type' => 'redis',
        ];
    }
}

/**
 * Custom Cloud Storage Backend Example
 * 
 * Simulates storing snapshots in cloud storage with metadata
 */
class CloudStorage implements StorageInterface
{
    private array $buckets = [];
    private string $bucketName;
    
    public function __construct(string $bucketName = 'snapshots-bucket')
    {
        $this->bucketName = $bucketName;
        $this->buckets[$bucketName] = [];
        echo "   Cloud Storage initialized with bucket: {$bucketName}\n";
    }
    
    public function save(string $label, array $data): array
    {
        $snapshot = [
            'label' => $label,
            'data' => $data,
            'stored_at' => time(),
            'storage_type' => 'cloud',
            'bucket' => $this->bucketName,
            'object_key' => 'snapshots/' . date('Y/m/d') . '/' . $label . '.json',
            'metadata' => [
                'created_by' => 'laravel-snapshot',
                'version' => '1.0',
                'content_type' => 'application/json',
            ],
        ];
        
        $this->buckets[$this->bucketName][$label] = $snapshot;
        
        echo "   ✓ Uploaded snapshot '{$label}' to cloud storage: {$snapshot['object_key']}\n";
        
        return $snapshot;
    }
    
    public function load(string $label): ?array
    {
        if (!isset($this->buckets[$this->bucketName][$label])) {
            return null;
        }
        
        $snapshot = $this->buckets[$this->bucketName][$label];
        echo "   ✓ Downloaded snapshot '{$label}' from cloud storage\n";
        
        return $snapshot;
    }
    
    public function list(): array
    {
        $result = [];
        foreach ($this->buckets[$this->bucketName] as $label => $snapshot) {
            $result[$label] = [
                'timestamp' => date('Y-m-d H:i:s', $snapshot['stored_at']),
                'object_key' => $snapshot['object_key'],
                'bucket' => $snapshot['bucket'],
                'storage_type' => 'cloud',
                'data' => $snapshot['data'],
            ];
        }
        
        return $result;
    }
    
    public function delete(string $label): bool
    {
        if (isset($this->buckets[$this->bucketName][$label])) {
            $objectKey = $this->buckets[$this->bucketName][$label]['object_key'];
            unset($this->buckets[$this->bucketName][$label]);
            echo "   ✓ Deleted snapshot '{$label}' from cloud storage: {$objectKey}\n";
            return true;
        }
        
        return false;
    }
    
    public function clear(?string $modelClass = null): int
    {
        $count = 0;
        $toDelete = [];
        
        foreach ($this->buckets[$this->bucketName] as $label => $snapshot) {
            if ($modelClass === null) {
                $toDelete[] = $label;
            } else {
                $data = $snapshot['data'];
                if (isset($data['class']) && $data['class'] === $modelClass) {
                    $toDelete[] = $label;
                }
            }
        }
        
        foreach ($toDelete as $label) {
            unset($this->buckets[$this->bucketName][$label]);
            $count++;
        }
        
        echo "   ✓ Cleared {$count} snapshots from cloud storage\n";
        
        return $count;
    }
}

echo "=== Custom Storage Backend Example ===\n\n";

// Demonstrate Redis Storage Backend
echo "1. Testing Redis Storage Backend:\n";
$redisStorage = new RedisStorage(1800); // 30 minutes TTL

// Set custom storage
Snapshot::setStorage($redisStorage);

// Create test data
$testData = [
    'id' => 1,
    'name' => 'Test User',
    'email' => 'test@example.com',
    'settings' => [
        'theme' => 'dark',
        'notifications' => true,
        'language' => 'en',
    ],
];

// Save snapshots
echo "\n   Saving snapshots to Redis:\n";
Snapshot::save($testData, 'redis-test-1');
Snapshot::save(array_merge($testData, ['name' => 'Updated User']), 'redis-test-2');

// Create large snapshot to test compression
$largeData = $testData;
$largeData['large_field'] = str_repeat('Lorem ipsum dolor sit amet. ', 100);
Snapshot::save($largeData, 'redis-large-snapshot');

// List snapshots
echo "\n   Redis Storage Contents:\n";
$redisSnapshots = Snapshot::list();
foreach ($redisSnapshots as $label => $info) {
    $ttl = $info['ttl_remaining'];
    $compressed = $info['compressed'] ? '(compressed)' : '';
    echo "     - {$label}: TTL {$ttl}s {$compressed}\n";
}

// Show Redis storage stats
echo "\n   Redis Storage Statistics:\n";
$redisStats = $redisStorage->getStats();
foreach ($redisStats as $metric => $value) {
    echo "     - {$metric}: {$value}\n";
}

// Test snapshot loading
echo "\n   Loading snapshot from Redis:\n";
$loaded = Snapshot::load('redis-test-1');
if ($loaded) {
    echo "     ✓ Loaded: {$loaded['data']['name']}\n";
}

// Demonstrate Cloud Storage Backend
echo "\n2. Testing Cloud Storage Backend:\n";
$cloudStorage = new CloudStorage('my-app-snapshots');

// Switch to cloud storage
Snapshot::setStorage($cloudStorage);

echo "\n   Saving snapshots to cloud storage:\n";
Snapshot::save($testData, 'cloud-test-1');
Snapshot::save(['type' => 'config', 'settings' => ['debug' => true]], 'cloud-config');

// List cloud snapshots
echo "\n   Cloud Storage Contents:\n";
$cloudSnapshots = Snapshot::list();
foreach ($cloudSnapshots as $label => $info) {
    echo "     - {$label}: {$info['object_key']}\n";
}

// Demonstrate storage comparison
echo "\n3. Storage Backend Comparison:\n";

// Compare features
$comparison = [
    'Feature' => ['Redis', 'Cloud', 'Database', 'File'],
    'TTL Support' => ['✓', '✗', '✗', '✗'],
    'Compression' => ['✓', '✗', '✗', '✗'],
    'Scalability' => ['High', 'Very High', 'Medium', 'Low'],
    'Durability' => ['Medium', 'Very High', 'High', 'Medium'],
    'Query Speed' => ['Very Fast', 'Medium', 'Fast', 'Slow'],
    'Cost' => ['Medium', 'Variable', 'Low', 'Very Low'],
];

echo "\n   Storage Backend Feature Comparison:\n";
$features = array_keys($comparison);
foreach ($features as $feature) {
    if ($feature === 'Feature') continue;
    $values = $comparison[$feature];
    echo "     {$feature}:\n";
    echo "       - Redis: {$values[0]}\n";
    echo "       - Cloud: {$values[1]}\n";
    echo "       - Database: {$values[2]}\n";
    echo "       - File: {$values[3]}\n";
}

// Demonstrate storage switching
echo "\n4. Runtime Storage Switching:\n";

// Save same data to different backends
$switchTestData = ['id' => 999, 'test' => 'storage switching'];

// Save to cloud
Snapshot::setStorage($cloudStorage);
Snapshot::save($switchTestData, 'switch-test');
echo "   ✓ Saved to cloud storage\n";

// Switch to Redis and save
Snapshot::setStorage($redisStorage);
Snapshot::save($switchTestData, 'switch-test');
echo "   ✓ Saved to Redis storage\n";

// Demonstrate that they're separate
$cloudCount = count($cloudStorage->list());
$redisCount = count($redisStorage->list());
echo "   - Cloud storage has {$cloudCount} snapshots\n";
echo "   - Redis storage has {$redisCount} snapshots\n";

// Configuration example for custom storage
echo "\n5. Configuration Integration Example:\n";
echo "   To integrate custom storage in Laravel config/snapshot.php:\n\n";

$configExample = <<<'PHP'
// config/snapshot.php
'drivers' => [
    'redis' => [
        'driver' => 'custom',
        'class' => RedisStorage::class,
        'ttl' => 3600,
        'connection' => 'default',
    ],
    'cloud' => [
        'driver' => 'custom', 
        'class' => CloudStorage::class,
        'bucket' => env('SNAPSHOT_BUCKET', 'snapshots'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
],

PHP;

echo $configExample;

// Best practices
echo "\n6. Custom Storage Best Practices:\n";
echo "   ✓ Implement proper error handling\n";
echo "   ✓ Add connection pooling for performance\n";
echo "   ✓ Include retry logic for network failures\n";
echo "   ✓ Implement proper serialization/deserialization\n";
echo "   ✓ Add compression for large snapshots\n";
echo "   ✓ Include TTL/expiration support\n";
echo "   ✓ Provide storage statistics and monitoring\n";
echo "   ✓ Handle concurrent access properly\n";
echo "   ✓ Include backup and recovery mechanisms\n";

// Performance considerations
echo "\n7. Performance Considerations:\n";
echo "   - Redis: Excellent for frequently accessed snapshots\n";
echo "   - Cloud: Best for long-term storage and archival\n";
echo "   - Database: Good balance of features and performance\n";
echo "   - File: Suitable for development and small-scale use\n";

// Cleanup
echo "\n8. Cleaning up test data:\n";
$redisCleared = $redisStorage->clear();
$cloudCleared = $cloudStorage->clear();
echo "   ✓ Cleared {$redisCleared} snapshots from Redis\n";
echo "   ✓ Cleared {$cloudCleared} snapshots from Cloud\n";

echo "\n=== Custom Storage Benefits Demonstrated ===\n";
echo "✓ TTL/expiration support for Redis backend\n";
echo "✓ Compression for large snapshots\n";
echo "✓ Cloud storage integration\n";
echo "✓ Runtime storage backend switching\n";
echo "✓ Storage-specific optimizations\n";
echo "✓ Extensible architecture\n";
echo "✓ Performance monitoring\n";

echo "\nCustom storage backend example completed successfully!\n";