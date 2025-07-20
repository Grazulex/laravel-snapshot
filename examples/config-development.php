<?php

declare(strict_types=1);
/**
 * Example: Development Configuration
 * Description: Development-friendly configuration for Laravel Snapshot
 *
 * Prerequisites:
 * - Laravel application in development environment
 * - Laravel Snapshot package installed
 *
 * Usage:
 * This file demonstrates configuration examples for config/snapshot.php in development
 * Copy relevant sections to your configuration file
 */

echo "=== Development Configuration Example ===\n\n";

echo "1. Development-optimized config/snapshot.php:\n";

// Development configuration with debugging and convenience features
$developmentConfig = [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Driver
    |--------------------------------------------------------------------------
    */
    'default' => env('SNAPSHOT_DRIVER', 'file'), // File storage for easy inspection

    /*
    |--------------------------------------------------------------------------
    | Storage Drivers
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'database' => [
            'driver' => 'database',
            'connection' => null, // Use default connection
            'table' => 'snapshots',
            'compress' => false, // Disabled for easier debugging
            'encrypt' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('snapshots'),
            'compress' => false, // Keep files readable for debugging
            'format' => 'json',
            'permissions' => 0755,
            'pretty_print' => true, // Human-readable JSON
        ],

        'array' => [
            'driver' => 'array', // In-memory for testing
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Snapshots
    |--------------------------------------------------------------------------
    */
    'automatic' => [
        'enabled' => env('SNAPSHOT_AUTO_ENABLED', true),

        // Track all models during development
        'models' => [
            'App\Models\User' => ['created', 'updated', 'deleted'],
            'App\Models\Post' => ['created', 'updated', 'deleted'],
            'App\Models\Order' => ['created', 'updated'],
            'App\Models\Product' => ['created', 'updated'],
            // Add any model you're working on
            '*' => ['created', 'updated'], // Wildcard for all models (development only)
        ],

        // Minimal exclusions for development (keep most data for debugging)
        'exclude_fields' => [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ],

        // Capture all changes during development
        'min_changes' => 0,

        // Don't skip any snapshots during development
        'skip_when' => [
            'only_timestamps' => false,
            'recently_created_minutes' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Snapshots
    |--------------------------------------------------------------------------
    */
    'scheduled' => [
        'enabled' => env('SNAPSHOT_SCHEDULED_ENABLED', false), // Usually disabled in development

        'models' => [
            // Minimal scheduled snapshots for development
            'App\Models\User' => [
                'frequency' => 'hourly', // More frequent for testing
                'limit' => 10, // Small limit for development
            ],
        ],

        // No queues in development (synchronous for easier debugging)
        'queue' => [
            'enabled' => false,
            'connection' => 'sync',
            'queue' => 'default',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'enabled' => false, // Disabled to keep all snapshots during development
        
        // Short retention for development if enabled
        'default_days' => 7,

        'models' => [
            // Keep all development snapshots
            '*' => -1, // Never expire
        ],

        'by_event_type' => [
            'manual' => -1,
            'created' => -1,
            'updated' => -1,
            'deleted' => -1,
            'scheduled' => 7,
        ],

        'auto_cleanup' => [
            'enabled' => false, // Manual cleanup in development
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    */
    'performance' => [
        // No compression for easier debugging
        'compression' => [
            'enabled' => false,
        ],

        // Smaller batches for development
        'batch' => [
            'size' => 10,
            'delay_ms' => 0, // No delay in development
        ],

        // Relaxed memory limits for development
        'memory' => [
            'max_memory_mb' => 128,
            'gc_probability' => 0, // Disable to avoid interfering with debugging
        ],

        'database' => [
            'use_transactions' => false, // Easier to debug without transactions
            'chunk_size' => 50,
            'connection_timeout' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encryption' => [
            'enabled' => false, // Disabled for easier debugging
        ],

        'access' => [
            'require_auth' => false, // Open access in development
            'permissions' => [],
            'allowed_ips' => '', // No IP restrictions in development
        ],

        'audit' => [
            'enabled' => true,
            'log_channel' => 'single', // Use simple log channel
            'log_level' => 'debug', // Verbose logging for development
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Features
    |--------------------------------------------------------------------------
    */
    'development' => [
        // Debug mode features
        'debug' => [
            'enabled' => env('APP_DEBUG', false),
            'log_all_operations' => true,
            'verbose_output' => true,
            'show_sql_queries' => true,
        ],

        // Development helpers
        'helpers' => [
            'auto_label_generation' => true, // Automatically generate descriptive labels
            'include_stack_trace' => true,   // Include stack trace in snapshots
            'include_request_data' => true,  // Include request data in snapshots
        ],

        // Testing features
        'testing' => [
            'reset_on_test' => true,        // Clear snapshots between tests
            'mock_storage' => 'array',      // Use array storage in tests
            'disable_events' => false,     // Keep events enabled for testing
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring (Development)
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => false, // Usually disabled in development

        'notifications' => [
            'channels' => ['log'], // Just log in development
        ],
    ],
];

echo "   Development-specific features:\n";
echo "   ✓ File storage for easy inspection\n";
echo "   ✓ No compression for readable files\n";
echo "   ✓ Track all models and events\n";
echo "   ✓ No retention policies (keep everything)\n";
echo "   ✓ Debug logging enabled\n";
echo "   ✓ No authentication required\n";
echo "   ✓ Synchronous processing\n";
echo "   ✓ Development helper features\n";

// Environment variables for development
echo "\n2. Development environment variables (.env):\n";

$envVars = <<<'ENV'
# Laravel Snapshot Configuration (Development)
APP_ENV=local
APP_DEBUG=true

SNAPSHOT_DRIVER=file
SNAPSHOT_AUTO_ENABLED=true
SNAPSHOT_SCHEDULED_ENABLED=false

# Development settings
SNAPSHOT_DEBUG=true
SNAPSHOT_COMPRESS=false
SNAPSHOT_ENCRYPT=false
SNAPSHOT_RETENTION_ENABLED=false

# Queue settings (use sync for immediate processing)
QUEUE_CONNECTION=sync

# Database
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

ENV;

echo $envVars;

// Development helpers
echo "\n3. Development helper utilities:\n";

$helperCode = <<<'PHP'
<?php
// Development helper functions (put in a Helper class or service provider)

if (app()->environment('local')) {
    
    // Quick snapshot inspection
    function inspect_snapshot(string $label): void
    {
        $snapshot = Snapshot::load($label);
        if ($snapshot) {
            dump($snapshot);
        } else {
            echo "Snapshot '{$label}' not found\n";
        }
    }

    // List recent snapshots
    function recent_snapshots(int $limit = 10): void
    {
        $snapshots = Snapshot::list();
        $recent = array_slice($snapshots, 0, $limit, true);
        
        echo "Recent snapshots:\n";
        foreach ($recent as $label => $snapshot) {
            echo "- {$label}: " . ($snapshot['timestamp'] ?? 'N/A') . "\n";
        }
    }

    // Clear all snapshots (development only)
    function clear_snapshots(): void
    {
        if (app()->environment('production')) {
            throw new Exception('Cannot clear snapshots in production');
        }
        
        $count = Snapshot::clear();
        echo "Cleared {$count} snapshots\n";
    }

    // Compare model with its latest snapshot
    function compare_with_latest($model): void
    {
        $latest = $model->getLatestSnapshot();
        if ($latest) {
            $diff = $model->compareWithSnapshot($latest['id']);
            dump($diff);
        } else {
            echo "No snapshots found for model\n";
        }
    }

    // Development snapshot with context
    function debug_snapshot($model, string $label = null): void
    {
        $label = $label ?? 'debug-' . date('H-i-s');
        
        // Add development context
        $context = [
            'file' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'] ?? 'unknown',
            'line' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['line'] ?? 'unknown',
            'user' => auth()->id() ?? null,
            'session' => session()->getId(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
        ];
        
        $snapshot = Snapshot::save($model, $label);
        $snapshot['debug_context'] = $context;
        
        echo "Debug snapshot created: {$label}\n";
        echo "Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
}

PHP;

echo $helperCode;

// Database setup for development
echo "\n4. Database setup for development:\n";

$databaseSetup = <<<'SQL'
-- Simple SQLite setup for development
-- No complex indexes needed, just basic ones for functionality

CREATE INDEX idx_snapshots_model ON snapshots(model_type, model_id);
CREATE INDEX idx_snapshots_created ON snapshots(created_at);

-- Enable WAL mode for better concurrent access during development
PRAGMA journal_mode=WAL;

SQL;

echo $databaseSetup;

// Artisan commands for development
echo "\n5. Development workflow commands:\n";

$devCommands = <<<'BASH'
# Development workflow commands

# Clear all snapshots
php artisan snapshot:clear

# Create a manual snapshot for debugging
php artisan snapshot:save "App\Models\User" --id=1 --label=debug-user-state

# Generate a development report
php artisan snapshot:report "App\Models\User" --id=1 --format=json

# List snapshots for a specific model
php artisan snapshot:list --model="App\Models\User"

# Compare two snapshots
php artisan snapshot:diff snapshot-1 snapshot-2

# Run tests with snapshot assertions
php artisan test --filter=SnapshotTest

BASH;

echo $devCommands;

// IDE integration
echo "\n6. IDE integration (VS Code settings.json):\n";

$ideConfig = <<<'JSON'
{
    "files.associations": {
        "storage/snapshots/*.json": "json"
    },
    "json.format.enable": true,
    "json.maxItemsComputed": 1000,
    "files.exclude": {
        "storage/snapshots/**/*.gz": true
    },
    "search.exclude": {
        "storage/snapshots/": true
    }
}

JSON;

echo $ideConfig;

// Development testing setup
echo "\n7. Testing configuration:\n";

$testConfig = <<<'PHP'
<?php
// tests/TestCase.php

use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;

abstract class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Use array storage for tests (in-memory, auto-cleanup)
        Snapshot::setStorage(new ArrayStorage());
    }

    protected function tearDown(): void
    {
        // Cleanup snapshots after each test
        Snapshot::clear();

        parent::tearDown();
    }

    // Helper method for snapshot assertions
    protected function assertSnapshotExists(string $label): void
    {
        $this->assertNotNull(Snapshot::load($label), "Snapshot '{$label}' should exist");
    }

    protected function assertSnapshotNotExists(string $label): void
    {
        $this->assertNull(Snapshot::load($label), "Snapshot '{$label}' should not exist");
    }

    protected function assertSnapshotHasData(string $label, array $expectedData): void
    {
        $snapshot = Snapshot::load($label);
        $this->assertNotNull($snapshot, "Snapshot '{$label}' should exist");
        
        $actualData = $snapshot['data'] ?? $snapshot;
        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $actualData[$key], "Snapshot data '{$key}' mismatch");
        }
    }
}

PHP;

echo $testConfig;

echo "\n8. Development debugging tips:\n";
echo "   ✓ Use file storage to inspect snapshots in storage/snapshots/\n";
echo "   ✓ Enable debug logging to see all snapshot operations\n";
echo "   ✓ Use array storage in tests for isolation\n";
echo "   ✓ Create debug helper functions for quick snapshot inspection\n";
echo "   ✓ Use descriptive labels with timestamps for easy identification\n";
echo "   ✓ Keep retention disabled to preserve debugging data\n";
echo "   ✓ Use synchronous queues for immediate processing\n";
echo "   ✓ Add development-only routes for snapshot management\n";

echo "\n9. Development workflow example:\n";

$workflowExample = <<<'PHP'
// Development workflow example

// 1. Start working on a feature
$user = User::find(1);
debug_snapshot($user, 'before-feature-work');

// 2. Make changes
$user->update(['name' => 'Updated Name']);

// 3. Create snapshot after changes
debug_snapshot($user, 'after-feature-work');

// 4. Compare changes
$diff = Snapshot::diff('before-feature-work', 'after-feature-work');
dump($diff);

// 5. Test the feature
$this->assertSnapshotHasData('after-feature-work', [
    'name' => 'Updated Name'
]);

// 6. Clean up when done
clear_snapshots();

PHP;

echo $workflowExample;

echo "\nDevelopment configuration example completed successfully!\n";