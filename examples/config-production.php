<?php

declare(strict_types=1);
/**
 * Example: Production Configuration
 * Description: Production-ready configuration for Laravel Snapshot
 *
 * Prerequisites:
 * - Laravel application in production environment
 * - Laravel Snapshot package installed
 *
 * Usage:
 * This file demonstrates configuration examples for config/snapshot.php
 * Copy relevant sections to your configuration file
 */

echo "=== Production Configuration Example ===\n\n";

echo "1. Production-optimized config/snapshot.php:\n";

// Complete production configuration
$productionConfig = [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Driver
    |--------------------------------------------------------------------------
    */
    'default' => env('SNAPSHOT_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Storage Drivers
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('SNAPSHOT_DB_CONNECTION', null),
            'table' => 'snapshots',
            'compress' => true,
            'encrypt' => false, // Enable if storing sensitive data
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('snapshots'),
            'compress' => true,
            'format' => 'json', // json, yaml
            'permissions' => 0755,
        ],

        'cloud' => [
            'driver' => 'file', // Use file driver but on cloud storage mount
            'path' => env('SNAPSHOT_CLOUD_PATH', '/mnt/snapshots'),
            'compress' => true,
            'backup_locally' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Snapshots
    |--------------------------------------------------------------------------
    */
    'automatic' => [
        'enabled' => env('SNAPSHOT_AUTO_ENABLED', true),

        // Models and their monitored events
        'models' => [
            'App\Models\User' => ['created', 'updated'], // No 'deleted' in production
            'App\Models\Order' => ['created', 'updated'],
            'App\Models\Payment' => ['created'], // Only capture creation for audit
            'App\Models\Product' => ['updated'], // Only track changes
        ],

        // Fields to exclude from snapshots (security/privacy)
        'exclude_fields' => [
            'password',
            'remember_token', 
            'two_factor_secret',
            'two_factor_recovery_codes',
            'api_token',
            'stripe_id',
            'pm_type',
            'pm_last_four',
            'trial_ends_at',
            'created_at', // Usually not needed in snapshots
            'updated_at', // Usually not needed in snapshots
        ],

        // Only snapshot if model has changed significantly
        'min_changes' => 1,

        // Skip snapshots for certain conditions
        'skip_when' => [
            // Skip if only timestamps changed
            'only_timestamps' => true,
            // Skip if model was created less than X minutes ago
            'recently_created_minutes' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Snapshots
    |--------------------------------------------------------------------------
    */
    'scheduled' => [
        'enabled' => env('SNAPSHOT_SCHEDULED_ENABLED', true),

        'models' => [
            'App\Models\User' => [
                'frequency' => 'daily',
                'time' => '02:00',
                'limit' => 1000, // Limit for performance
                'conditions' => ['status' => 'active'], // Only active users
            ],
            
            'App\Models\Order' => [
                'frequency' => 'hourly',
                'limit' => 500,
                'conditions' => ['status' => ['processing', 'shipped']], // Active orders only
            ],

            'App\Models\SystemConfig' => [
                'frequency' => 'weekly',
                'day' => 'sunday',
                'time' => '01:00',
            ],
        ],

        // Queue configuration for scheduled snapshots
        'queue' => [
            'enabled' => true,
            'connection' => 'redis',
            'queue' => 'snapshots',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'enabled' => true,
        
        // Default retention (can be overridden per model)
        'default_days' => 90,

        // Model-specific retention policies
        'models' => [
            'App\Models\User' => 365,        // Keep user changes for 1 year
            'App\Models\Order' => 2555,      // Keep order data for 7 years (compliance)
            'App\Models\Payment' => 2555,    // Keep payment data for 7 years (compliance)
            'App\Models\Product' => 180,     // Keep product changes for 6 months
            'App\Models\SystemConfig' => -1, // Keep forever (-1 = no expiration)
        ],

        // Retention by event type
        'by_event_type' => [
            'manual' => 180,     // Manual snapshots for 6 months
            'created' => 365,    // Creation snapshots for 1 year
            'updated' => 90,     // Update snapshots for 3 months
            'deleted' => 2555,   // Deletion snapshots for 7 years
            'scheduled' => 30,   // Scheduled snapshots for 1 month
        ],

        // Automatic cleanup
        'auto_cleanup' => [
            'enabled' => true,
            'schedule' => 'daily', // Run cleanup daily
            'time' => '03:00',
            'chunk_size' => 1000, // Process in chunks to avoid memory issues
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    */
    'performance' => [
        // Compression for storage efficiency
        'compression' => [
            'enabled' => true,
            'algorithm' => 'gzip', // gzip, lz4
            'level' => 6, // 1-9 for gzip
            'min_size' => 1024, // Only compress if larger than 1KB
        ],

        // Batch processing
        'batch' => [
            'size' => 100, // Process snapshots in batches of 100
            'delay_ms' => 10, // Delay between batches to reduce load
        ],

        // Memory management
        'memory' => [
            'max_memory_mb' => 256,
            'gc_probability' => 10, // 1 in 10 chance to run garbage collection
        ],

        // Database optimization
        'database' => [
            'use_transactions' => true,
            'chunk_size' => 500,
            'connection_timeout' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Encryption for sensitive snapshots
        'encryption' => [
            'enabled' => env('SNAPSHOT_ENCRYPT', false),
            'cipher' => 'AES-256-CBC',
            'key' => env('SNAPSHOT_ENCRYPT_KEY'),
        ],

        // Access control
        'access' => [
            // Require authentication to view snapshots
            'require_auth' => true,
            
            // Permissions required to access snapshots
            'permissions' => [
                'view' => 'snapshot.view',
                'create' => 'snapshot.create',
                'delete' => 'snapshot.delete',
                'restore' => 'snapshot.restore',
            ],

            // IP whitelist for snapshot operations
            'allowed_ips' => env('SNAPSHOT_ALLOWED_IPS', ''),
        ],

        // Audit logging
        'audit' => [
            'enabled' => true,
            'log_channel' => 'snapshots',
            'log_level' => 'info',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerts
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => true,

        // Storage usage alerts
        'storage_alerts' => [
            'disk_usage_threshold' => 85, // Alert at 85% disk usage
            'snapshot_count_threshold' => 100000, // Alert at 100k snapshots
        ],

        // Performance alerts
        'performance_alerts' => [
            'slow_snapshot_threshold_ms' => 5000, // Alert if snapshot takes >5s
            'memory_threshold_mb' => 512, // Alert if memory usage >512MB
        ],

        // Notification channels
        'notifications' => [
            'channels' => ['mail', 'slack'],
            'recipients' => [
                'mail' => [env('ADMIN_EMAIL', 'admin@example.com')],
                'slack' => [env('SLACK_WEBHOOK_URL')],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup & Recovery
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('SNAPSHOT_BACKUP_ENABLED', true),

        // Backup destinations
        'destinations' => [
            's3' => [
                'disk' => 's3',
                'path' => 'snapshots/backups',
                'frequency' => 'daily',
            ],
            'local' => [
                'disk' => 'local',
                'path' => 'backups/snapshots',
                'frequency' => 'weekly',
            ],
        ],

        // Backup retention
        'retention' => [
            'daily' => 30,   // Keep daily backups for 30 days
            'weekly' => 52,  // Keep weekly backups for 52 weeks
            'monthly' => 12, // Keep monthly backups for 12 months
        ],
    ],
];

echo "   Configuration sections:\n";
echo "   ✓ Storage drivers (database, file, cloud)\n";
echo "   ✓ Automatic snapshots with exclusions\n";
echo "   ✓ Scheduled snapshots with queues\n";
echo "   ✓ Retention policies by model and event type\n";
echo "   ✓ Performance optimizations\n";
echo "   ✓ Security and access control\n";
echo "   ✓ Monitoring and alerts\n";
echo "   ✓ Backup and recovery\n";

// Environment variables for production
echo "\n2. Production environment variables (.env):\n";

$envVars = <<<'ENV'
# Laravel Snapshot Configuration
SNAPSHOT_DRIVER=database
SNAPSHOT_AUTO_ENABLED=true
SNAPSHOT_SCHEDULED_ENABLED=true
SNAPSHOT_DB_CONNECTION=mysql
SNAPSHOT_ENCRYPT=false
SNAPSHOT_BACKUP_ENABLED=true

# Performance
SNAPSHOT_COMPRESS=true
SNAPSHOT_QUEUE_CONNECTION=redis
SNAPSHOT_QUEUE_NAME=snapshots

# Security
SNAPSHOT_ENCRYPT_KEY=
SNAPSHOT_ALLOWED_IPS="10.0.0.0/8,192.168.0.0/16"

# Monitoring
ADMIN_EMAIL=admin@yourcompany.com
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...

# Storage paths (if using file driver)
SNAPSHOT_CLOUD_PATH=/mnt/shared-storage/snapshots

ENV;

echo $envVars;

// Database optimizations
echo "\n3. Database optimizations:\n";

$databaseOptimizations = <<<'SQL'
-- Indexes for performance
CREATE INDEX idx_snapshots_model_type_id ON snapshots(model_type, model_id);
CREATE INDEX idx_snapshots_created_at ON snapshots(created_at);
CREATE INDEX idx_snapshots_event_type ON snapshots(event_type);
CREATE INDEX idx_snapshots_label ON snapshots(label);

-- Partitioning by date (MySQL 8.0+)
ALTER TABLE snapshots PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Table optimization for large datasets
OPTIMIZE TABLE snapshots;

SQL;

echo $databaseOptimizations;

// Nginx/Apache configuration
echo "\n4. Web server configuration (Nginx):\n";

$nginxConfig = <<<'NGINX'
# Protect snapshot files from direct access
location /storage/snapshots {
    deny all;
    return 404;
}

# Protect snapshot API endpoints
location /api/snapshots {
    # Add rate limiting
    limit_req zone=api burst=10 nodelay;
    
    # Require authentication
    auth_basic "Snapshot API";
    auth_basic_user_file /etc/nginx/.htpasswd;
    
    try_files $uri $uri/ /index.php?$query_string;
}

NGINX;

echo $nginxConfig;

// Queue configuration
echo "\n5. Queue worker configuration:\n";

$queueConfig = <<<'BASH'
# Supervisor configuration for snapshot queue workers
[program:snapshot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --queue=snapshots --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/snapshot-worker.log
stopwaitsecs=3600

BASH;

echo $queueConfig;

// Cron configuration for scheduled tasks
echo "\n6. Cron configuration:\n";

$cronConfig = <<<'CRON'
# Laravel Snapshot scheduled tasks
# Run Laravel scheduler every minute
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1

# Manual snapshot cleanup (backup)
0 4 * * * cd /var/www/html && php artisan snapshot:clear --before=$(date -d '90 days ago' +%Y-%m-%d) >> /var/log/snapshot-cleanup.log 2>&1

# Storage monitoring
0 8 * * * cd /var/www/html && php artisan snapshot:monitor >> /var/log/snapshot-monitor.log 2>&1

CRON;

echo $cronConfig;

// Monitoring and alerting
echo "\n7. Application monitoring integration:\n";

$monitoringCode = <<<'PHP'
// In AppServiceProvider or custom service
public function boot()
{
    // Monitor snapshot creation performance
    Event::listen(SnapshotCreated::class, function ($event) {
        $duration = $event->duration;
        
        if ($duration > 5000) { // 5 seconds
            Log::warning('Slow snapshot creation', [
                'duration_ms' => $duration,
                'model' => $event->modelClass,
                'model_id' => $event->modelId,
            ]);
            
            // Send alert to monitoring service
            app(MonitoringService::class)->alert('slow_snapshot', [
                'duration' => $duration,
                'threshold' => 5000,
            ]);
        }
    });
    
    // Monitor storage usage
    Schedule::call(function () {
        $stats = Snapshot::stats()->storage();
        
        if ($stats['disk_usage_percent'] > 85) {
            app(AlertService::class)->send('High snapshot storage usage', [
                'usage_percent' => $stats['disk_usage_percent'],
                'total_size' => $stats['total_size'],
                'snapshot_count' => $stats['count'],
            ]);
        }
    })->hourly();
}

PHP;

echo $monitoringCode;

echo "\n8. Production deployment checklist:\n";
echo "   ✓ Database indexes created\n";
echo "   ✓ Queue workers configured and running\n";
echo "   ✓ Cron jobs scheduled\n";
echo "   ✓ Storage paths have correct permissions\n";
echo "   ✓ Environment variables set\n";
echo "   ✓ Monitoring and alerting configured\n";
echo "   ✓ Backup strategy implemented\n";
echo "   ✓ Security measures in place\n";
echo "   ✓ Performance optimizations applied\n";
echo "   ✓ Error logging configured\n";

echo "\n9. Production best practices:\n";
echo "   ✓ Use database storage for production (better performance, querying)\n";
echo "   ✓ Enable compression to reduce storage usage\n";
echo "   ✓ Set appropriate retention policies (balance audit needs vs storage)\n";
echo "   ✓ Use queues for scheduled snapshots to avoid blocking\n";
echo "   ✓ Monitor storage usage and performance metrics\n";
echo "   ✓ Exclude sensitive fields (passwords, tokens, etc.)\n";
echo "   ✓ Implement proper access control and authentication\n";
echo "   ✓ Regular backup of snapshot data\n";
echo "   ✓ Test restore procedures periodically\n";
echo "   ✓ Use read replicas for reporting if high volume\n";

echo "\nProduction configuration example completed successfully!\n";