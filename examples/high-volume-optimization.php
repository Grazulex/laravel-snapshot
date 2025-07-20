<?php

declare(strict_types=1);
/**
 * Example: High Volume Optimization
 * Description: Optimization strategies for high-traffic applications with many snapshots
 *
 * Prerequisites:
 * - Laravel Snapshot package configured
 * - High-volume application scenario
 *
 * Usage:
 * This demonstrates optimization techniques for applications with many snapshots
 */

echo "=== High Volume Optimization Example ===\n\n";

echo "1. Optimized storage configuration:\n";

$optimizedConfig = <<<'PHP'
<?php
// config/snapshot.php - High volume optimizations

return [
    'default' => 'database',
    
    'drivers' => [
        'database' => [
            'driver' => 'database',
            'connection' => 'snapshots', // Dedicated connection
            'table' => 'snapshots',
            'compress' => true, // Essential for large volumes
            'batch_size' => 500, // Process in batches
        ],
        
        // Read replica for reporting
        'database_read' => [
            'driver' => 'database',
            'connection' => 'snapshots_read',
            'table' => 'snapshots',
            'read_only' => true,
        ],
    ],
    
    'automatic' => [
        'enabled' => true,
        'queue' => [
            'enabled' => true,
            'connection' => 'redis',
            'queue' => 'snapshots-high-priority',
        ],
        
        // Selective model tracking
        'models' => [
            'App\Models\CriticalModel' => ['created', 'updated'],
            'App\Models\AuditModel' => ['created', 'deleted'],
            // Skip high-frequency, low-value models
        ],
        
        'exclude_fields' => [
            'created_at', 'updated_at', 'last_activity',
            'view_count', 'click_count', // High-frequency change fields
        ],
        
        // Advanced filtering
        'conditions' => [
            'App\Models\User' => [
                'only_if' => function($model) {
                    // Only snapshot significant changes
                    return $model->isDirty(['email', 'status', 'role']);
                },
            ],
        ],
    ],
    
    'performance' => [
        'compression' => [
            'enabled' => true,
            'algorithm' => 'lz4', // Faster than gzip
            'level' => 3, // Balance compression vs speed
        ],
        
        'batch' => [
            'size' => 1000, // Larger batches for efficiency
            'timeout' => 300, // 5 minute timeout
        ],
        
        'memory' => [
            'max_memory_mb' => 512,
            'chunk_processing' => true,
            'gc_after_batch' => true,
        ],
        
        'database' => [
            'use_transactions' => true,
            'chunk_size' => 1000,
            'prepared_statements' => true,
            'connection_pooling' => true,
        ],
        
        'caching' => [
            'metadata_cache' => true,
            'stats_cache_ttl' => 300, // 5 minutes
            'list_cache_ttl' => 60,   // 1 minute
        ],
    ],
    
    'retention' => [
        'enabled' => true,
        'strategy' => 'tiered', // Keep recent snapshots, archive old ones
        
        'tiers' => [
            'hot' => ['days' => 7, 'storage' => 'database'],
            'warm' => ['days' => 30, 'storage' => 'file_compressed'],
            'cold' => ['days' => 365, 'storage' => 's3_glacier'],
            'archive' => ['days' => 2555, 'storage' => 's3_deep_archive'],
        ],
        
        'cleanup' => [
            'batch_size' => 5000,
            'parallel_workers' => 4,
            'schedule' => '0 2 * * *', // 2 AM daily
        ],
    ],
];

PHP;

echo $optimizedConfig;

echo "\n2. Database optimizations:\n";

$dbOptimizations = <<<'SQL'
-- High-performance database schema optimizations

-- Partitioned table for large datasets
CREATE TABLE snapshots (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    model_type varchar(255) NOT NULL,
    model_id varchar(255) NOT NULL,
    label varchar(255) NOT NULL,
    event_type varchar(50) NOT NULL,
    data longtext,
    metadata json,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id, created_at),
    UNIQUE KEY uk_snapshots_label (label),
    KEY idx_snapshots_model (model_type, model_id),
    KEY idx_snapshots_created_at (created_at),
    KEY idx_snapshots_event_type (event_type)
) ENGINE=InnoDB
PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p_2024_q1 VALUES LESS THAN (UNIX_TIMESTAMP('2024-04-01')),
    PARTITION p_2024_q2 VALUES LESS THAN (UNIX_TIMESTAMP('2024-07-01')),
    PARTITION p_2024_q3 VALUES LESS THAN (UNIX_TIMESTAMP('2024-10-01')),
    PARTITION p_2024_q4 VALUES LESS THAN (UNIX_TIMESTAMP('2025-01-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Separate table for snapshot statistics (pre-computed)
CREATE TABLE snapshot_stats (
    model_type varchar(255) NOT NULL,
    model_id varchar(255) NOT NULL,
    total_snapshots int unsigned DEFAULT 0,
    last_snapshot_at timestamp NULL,
    created_snapshots int unsigned DEFAULT 0,
    updated_snapshots int unsigned DEFAULT 0,
    deleted_snapshots int unsigned DEFAULT 0,
    PRIMARY KEY (model_type, model_id),
    KEY idx_stats_last_snapshot (last_snapshot_at)
) ENGINE=InnoDB;

-- Archive table for old snapshots
CREATE TABLE snapshots_archive LIKE snapshots;

-- Indexes for performance
CREATE INDEX idx_snapshots_composite ON snapshots(model_type, created_at, event_type);
CREATE INDEX idx_snapshots_label_prefix ON snapshots(label(50)); -- For prefix searches

-- Full-text search if needed
ALTER TABLE snapshots ADD FULLTEXT(data);

SQL;

echo $dbOptimizations;

echo "\n3. Queue optimization for high volume:\n";

$queueOptimizations = <<<'PHP'
<?php
// High-volume queue processing

class OptimizedSnapshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [10, 30, 60]; // Exponential backoff

    protected $batchData;
    protected $batchSize;

    public function __construct(array $batchData, int $batchSize = 100)
    {
        $this->batchData = $batchData;
        $this->batchSize = $batchSize;
        
        // Use lower priority queue for bulk operations
        $this->onQueue('snapshots-bulk');
    }

    public function handle()
    {
        $chunks = array_chunk($this->batchData, $this->batchSize);
        
        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk) {
                $this->processBatch($chunk);
            });
            
            // Prevent memory leaks
            if (memory_get_usage() > 256 * 1024 * 1024) { // 256MB
                gc_collect_cycles();
            }
        }
    }

    protected function processBatch(array $batch): void
    {
        $snapshots = [];
        
        foreach ($batch as $item) {
            $snapshots[] = [
                'model_type' => $item['model_type'],
                'model_id' => $item['model_id'],
                'label' => $item['label'],
                'event_type' => $item['event_type'],
                'data' => gzcompress(json_encode($item['data'])), // Compress inline
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Bulk insert for efficiency
        DB::table('snapshots')->insert($snapshots);
        
        // Update statistics in batch
        $this->updateStatsBatch($batch);
    }

    protected function updateStatsBatch(array $batch): void
    {
        $stats = [];
        
        foreach ($batch as $item) {
            $key = $item['model_type'] . ':' . $item['model_id'];
            if (!isset($stats[$key])) {
                $stats[$key] = [
                    'model_type' => $item['model_type'],
                    'model_id' => $item['model_id'],
                    'count' => 0,
                    'events' => [],
                ];
            }
            
            $stats[$key]['count']++;
            $stats[$key]['events'][] = $item['event_type'];
        }
        
        // Batch update statistics
        foreach ($stats as $stat) {
            DB::table('snapshot_stats')
                ->updateOrInsert(
                    [
                        'model_type' => $stat['model_type'],
                        'model_id' => $stat['model_id']
                    ],
                    [
                        'total_snapshots' => DB::raw('total_snapshots + ' . $stat['count']),
                        'last_snapshot_at' => now(),
                    ]
                );
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Snapshot batch job failed', [
            'batch_size' => count($this->batchData),
            'error' => $exception->getMessage(),
        ]);
        
        // Re-queue individual items for retry
        foreach ($this->batchData as $item) {
            OptimizedSnapshotJob::dispatch([$item], 1)
                ->delay(now()->addMinutes(5));
        }
    }
}

// Supervisor configuration for queue workers
/*
[program:snapshot-workers]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work redis --queue=snapshots-bulk --sleep=3 --tries=3 --max-time=3600 --memory=512
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/snapshot-worker.log
stopwaitsecs=3600
*/

PHP;

echo $queueOptimizations;

echo "\n4. Caching strategies:\n";

$cachingStrategies = <<<'PHP'
<?php
// Caching for high-volume snapshot operations

class SnapshotCacheManager
{
    protected $redis;
    protected $statsCache;

    public function __construct()
    {
        $this->redis = Redis::connection('snapshots');
        $this->statsCache = Cache::store('redis');
    }

    // Cache snapshot metadata for quick listing
    public function cacheSnapshotMetadata(string $label, array $metadata): void
    {
        $key = "snapshot:meta:{$label}";
        $this->redis->hset($key, $metadata);
        $this->redis->expire($key, 3600); // 1 hour TTL
    }

    // Cached snapshot listing with pagination
    public function getCachedSnapshotList(int $page = 1, int $limit = 50, ?string $modelType = null): array
    {
        $cacheKey = "snapshots:list:{$page}:{$limit}:" . ($modelType ?? 'all');
        
        return $this->statsCache->remember($cacheKey, 300, function () use ($page, $limit, $modelType) {
            $query = DB::table('snapshots')
                ->select('label', 'model_type', 'model_id', 'event_type', 'created_at')
                ->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $limit)
                ->take($limit);

            if ($modelType) {
                $query->where('model_type', $modelType);
            }

            return $query->get()->toArray();
        });
    }

    // Cache statistics with background refresh
    public function getCachedStats(?string $modelType = null): array
    {
        $cacheKey = "snapshot:stats:" . ($modelType ?? 'global');
        
        return $this->statsCache->remember($cacheKey, 900, function () use ($modelType) { // 15 minutes
            return $this->computeStats($modelType);
        });
    }

    protected function computeStats(?string $modelType): array
    {
        $query = DB::table('snapshot_stats');
        
        if ($modelType) {
            $query->where('model_type', $modelType);
        }

        $totalSnapshots = $query->sum('total_snapshots');
        $totalModels = $query->count();
        
        $eventStats = DB::table('snapshots')
            ->select('event_type', DB::raw('count(*) as count'))
            ->when($modelType, fn($q) => $q->where('model_type', $modelType))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return [
            'total_snapshots' => $totalSnapshots,
            'total_models' => $totalModels,
            'events' => $eventStats,
            'computed_at' => now()->toISOString(),
        ];
    }

    // Cache warm-up for frequently accessed data
    public function warmUpCache(): void
    {
        // Pre-compute popular model statistics
        $popularModels = DB::table('snapshot_stats')
            ->orderBy('total_snapshots', 'desc')
            ->limit(100)
            ->pluck('model_type')
            ->unique()
            ->values();

        foreach ($popularModels as $modelType) {
            $this->getCachedStats($modelType);
        }

        // Pre-compute recent snapshot lists
        for ($page = 1; $page <= 10; $page++) {
            $this->getCachedSnapshotList($page, 50);
        }
    }

    // Background cache refresh job
    public function scheduleBackgroundRefresh(): void
    {
        Schedule::call(function () {
            $this->warmUpCache();
        })->everyFiveMinutes();
    }
}

PHP;

echo $cachingStrategies;

echo "\n5. Storage tiering implementation:\n";

$storageTiering = <<<'PHP'
<?php
// Automated storage tiering for snapshot lifecycle management

class SnapshotTieringService
{
    protected $tiers = [
        'hot' => ['days' => 7, 'storage' => 'database'],
        'warm' => ['days' => 30, 'storage' => 'file'],
        'cold' => ['days' => 365, 'storage' => 's3'],
        'archive' => ['days' => 2555, 'storage' => 's3_glacier'],
    ];

    public function processSnapshotTiering(): void
    {
        foreach ($this->tiers as $tier => $config) {
            $this->processTier($tier, $config);
        }
    }

    protected function processTier(string $tier, array $config): void
    {
        $cutoffDate = now()->subDays($config['days']);
        
        // Find snapshots that need to be moved to this tier
        $snapshots = DB::table('snapshots')
            ->where('created_at', '<=', $cutoffDate)
            ->where('storage_tier', '!=', $tier)
            ->limit(1000) // Process in batches
            ->get();

        foreach ($snapshots as $snapshot) {
            $this->moveSnapshotToTier($snapshot, $tier, $config);
        }
    }

    protected function moveSnapshotToTier($snapshot, string $tier, array $config): void
    {
        try {
            switch ($config['storage']) {
                case 's3':
                    $this->moveToS3($snapshot, 'STANDARD');
                    break;
                case 's3_glacier':
                    $this->moveToS3($snapshot, 'GLACIER');
                    break;
                case 'file':
                    $this->moveToFile($snapshot);
                    break;
            }

            // Update tier metadata
            DB::table('snapshots')
                ->where('id', $snapshot->id)
                ->update([
                    'storage_tier' => $tier,
                    'storage_path' => $this->getStoragePath($snapshot, $config),
                    'migrated_at' => now(),
                ]);

        } catch (Exception $e) {
            Log::error("Failed to move snapshot to {$tier}", [
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function moveToS3($snapshot, string $storageClass): void
    {
        $key = "snapshots/{$snapshot->id}/{$snapshot->label}.json.gz";
        $data = gzcompress($snapshot->data);

        Storage::disk('s3')->put($key, $data, [
            'StorageClass' => $storageClass,
            'Metadata' => [
                'original_created_at' => $snapshot->created_at,
                'model_type' => $snapshot->model_type,
                'model_id' => $snapshot->model_id,
            ],
        ]);
    }

    protected function moveToFile($snapshot): void
    {
        $path = storage_path("snapshots/warm/{$snapshot->id}.json.gz");
        $dir = dirname($path);
        
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($path, gzcompress($snapshot->data));
    }

    protected function getStoragePath($snapshot, array $config): string
    {
        switch ($config['storage']) {
            case 's3':
            case 's3_glacier':
                return "s3://snapshots/{$snapshot->id}/{$snapshot->label}.json.gz";
            case 'file':
                return storage_path("snapshots/warm/{$snapshot->id}.json.gz");
            default:
                return '';
        }
    }
}

// Scheduled tiering job
Schedule::call(function () {
    app(SnapshotTieringService::class)->processSnapshotTiering();
})->daily()->at('03:00');

PHP;

echo $storageTiering;

echo "\n6. Performance monitoring:\n";

$performanceMonitoring = <<<'PHP'
<?php
// Performance monitoring for high-volume environments

class SnapshotPerformanceMonitor
{
    public function recordMetric(string $operation, float $duration, array $context = []): void
    {
        // Record to time-series database (e.g., InfluxDB)
        $metric = [
            'measurement' => 'snapshot_operations',
            'tags' => [
                'operation' => $operation,
                'server' => gethostname(),
                'environment' => app()->environment(),
            ],
            'fields' => [
                'duration_ms' => $duration,
                'memory_usage' => memory_get_peak_usage(true),
            ],
            'timestamp' => now()->getTimestampMs(),
        ];

        // Send to monitoring system
        $this->sendToInfluxDB($metric);

        // Alert if performance degrades
        $this->checkPerformanceThresholds($operation, $duration);
    }

    protected function checkPerformanceThresholds(string $operation, float $duration): void
    {
        $thresholds = [
            'snapshot_create' => 5000, // 5 seconds
            'snapshot_load' => 1000,   // 1 second
            'snapshot_diff' => 3000,   // 3 seconds
            'snapshot_list' => 2000,   // 2 seconds
        ];

        if ($duration > ($thresholds[$operation] ?? 10000)) {
            Log::warning('Slow snapshot operation detected', [
                'operation' => $operation,
                'duration_ms' => $duration,
                'threshold_ms' => $thresholds[$operation] ?? 10000,
            ]);

            // Send alert to monitoring service
            $this->sendAlert($operation, $duration);
        }
    }

    public function getDashboardMetrics(): array
    {
        return Cache::remember('snapshot:dashboard:metrics', 300, function () {
            return [
                'operations_per_minute' => $this->getOperationsPerMinute(),
                'average_duration' => $this->getAverageDuration(),
                'error_rate' => $this->getErrorRate(),
                'storage_usage' => $this->getStorageUsage(),
                'queue_length' => $this->getQueueLength(),
                'cache_hit_rate' => $this->getCacheHitRate(),
            ];
        });
    }

    // Health check endpoint
    public function healthCheck(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
            'cache' => $this->checkCache(),
        ];

        $overallHealth = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return [
            'status' => $overallHealth ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ];
    }
}

// Middleware to track API performance
class SnapshotApiPerformanceMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
        
        app(SnapshotPerformanceMonitor::class)->recordMetric(
            'api_request',
            $duration,
            [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => $response->status(),
            ]
        );
        
        return $response;
    }
}

PHP;

echo $performanceMonitoring;

echo "\n7. Load testing results simulation:\n";

$loadTestResults = [
    'concurrent_users' => 1000,
    'snapshots_per_second' => 500,
    'average_response_time' => 250, // ms
    'p95_response_time' => 800, // ms
    'p99_response_time' => 1500, // ms
    'error_rate' => 0.1, // 0.1%
    'memory_usage_per_worker' => 256, // MB
    'cpu_usage' => 65, // %
    'storage_growth_per_day' => 10, // GB
];

echo "   Load test results (simulated):\n";
foreach ($loadTestResults as $metric => $value) {
    $unit = '';
    if (strpos($metric, 'time') !== false) $unit = ' ms';
    if (strpos($metric, 'rate') !== false) $unit = '%';
    if (strpos($metric, 'memory') !== false) $unit = ' MB';
    if (strpos($metric, 'cpu') !== false) $unit = '%';
    if (strpos($metric, 'storage') !== false) $unit = ' GB';
    if (strpos($metric, 'per_second') !== false) $unit = '/sec';
    
    echo "     - " . str_replace('_', ' ', $metric) . ": {$value}{$unit}\n";
}

echo "\n=== High Volume Optimization Benefits Demonstrated ===\n";
echo "✓ Database partitioning and indexing strategies\n";
echo "✓ Batch processing and queue optimization\n";
echo "✓ Multi-tier storage architecture\n";
echo "✓ Advanced caching strategies\n";
echo "✓ Performance monitoring and alerting\n";
echo "✓ Automated storage lifecycle management\n";
echo "✓ Memory and resource optimization\n";
echo "✓ Load balancing and scaling patterns\n";

echo "\nHigh volume optimization example completed successfully!\n";