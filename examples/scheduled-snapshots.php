<?php

declare(strict_types=1);
/**
 * Example: Scheduled Snapshots
 * Description: Demonstrates scheduled snapshot creation and management
 *
 * Prerequisites:
 * - Laravel Snapshot package configured
 * - Cron job or Laravel scheduler setup
 * - Multiple models for testing
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/scheduled-snapshots.php';
 * 
 * For production use:
 * Add to crontab: 0 2 * * * cd /path/to/app && php artisan snapshot:schedule "App\Models\User" --limit=1000
 */

use App\Models\User;
use Grazulex\LaravelSnapshot\Snapshot;
use Illuminate\Console\Scheduling\Schedule;

echo "=== Scheduled Snapshots Example ===\n\n";

// Create test users for scheduled snapshots
$users = User::factory()->count(5)->create();
echo "1. Created 5 test users for scheduling demonstration\n";

// Manual scheduled snapshot creation
echo "\n2. Creating manual scheduled snapshots...\n";

foreach ($users as $index => $user) {
    $result = Snapshot::scheduled($user, "daily-backup-" . now()->format('Y-m-d'));
    echo "   Created scheduled snapshot for User #{$user->id}\n";
}

// Demonstrate console command usage for scheduled snapshots
echo "\n3. Console command examples for scheduled snapshots:\n";
echo "   # Create daily snapshots for all users (limit to 100)\n";
echo "   php artisan snapshot:schedule \"App\\Models\\User\" --limit=100 --label=daily\n\n";

echo "   # Create snapshots for specific user\n"; 
echo "   php artisan snapshot:schedule \"App\\Models\\User\" --id=1 --label=hourly\n\n";

echo "   # Cron job examples:\n";
echo "   # Daily user snapshots at 2 AM\n";
echo "   0 2 * * * php artisan snapshot:schedule \"App\\Models\\User\" --limit=1000\n\n";

echo "   # Hourly order snapshots\n";
echo "   0 * * * * php artisan snapshot:schedule \"App\\Models\\Order\" --limit=500\n\n";

// Laravel Scheduler integration example
echo "4. Laravel Scheduler integration (add to app/Console/Kernel.php):\n\n";

$schedulerExample = <<<'PHP'
protected function schedule(Schedule $schedule)
{
    // Daily user snapshots
    $schedule->command('snapshot:schedule', ['App\Models\User', '--limit=1000'])
             ->dailyAt('02:00')
             ->withoutOverlapping()
             ->runInBackground();
    
    // Hourly order snapshots (business hours only)
    $schedule->command('snapshot:schedule', ['App\Models\Order', '--limit=500'])
             ->hourly()
             ->between('09:00', '18:00')
             ->weekdays()
             ->withoutOverlapping();
    
    // Weekly comprehensive snapshots
    $schedule->command('snapshot:schedule', ['App\Models\Product', '--limit=10000'])
             ->weekly()
             ->sundays()
             ->at('01:00');
    
    // Monthly archive snapshots
    $schedule->command('snapshot:schedule', ['App\Models\Invoice', '--limit=50000'])
             ->monthly()
             ->runInBackground()
             ->emailOutputOnFailure('admin@example.com');
}
PHP;

echo $schedulerExample . "\n\n";

// Configuration examples
echo "5. Configuration for scheduled snapshots (config/snapshot.php):\n\n";

$configExample = <<<'PHP'
'scheduled' => [
    'enabled' => true,
    'default_frequency' => 'daily',
    'models' => [
        'App\Models\User' => 'daily',
        'App\Models\Order' => 'hourly', 
        'App\Models\Product' => 'weekly',
        'App\Models\Invoice' => 'monthly',
    ],
],
PHP;

echo $configExample . "\n\n";

// Monitoring scheduled snapshots
echo "6. Monitoring scheduled snapshots...\n";

// Count recent scheduled snapshots
$recentScheduledCount = \Grazulex\LaravelSnapshot\Models\ModelSnapshot::where('event_type', 'scheduled')
    ->where('created_at', '>=', now()->subHours(24))
    ->count();

echo "   Recent scheduled snapshots (last 24h): {$recentScheduledCount}\n";

// Show scheduled snapshots by model
$scheduledByModel = \Grazulex\LaravelSnapshot\Models\ModelSnapshot::where('event_type', 'scheduled')
    ->selectRaw('model_type, count(*) as count')
    ->groupBy('model_type')
    ->pluck('count', 'model_type')
    ->toArray();

echo "   Scheduled snapshots by model:\n";
foreach ($scheduledByModel as $modelType => $count) {
    echo "     - {$modelType}: {$count}\n";
}

// Cleanup old scheduled snapshots
echo "\n7. Cleanup strategy for scheduled snapshots:\n";

$cleanupScript = <<<'BASH'
#!/bin/bash
# cleanup-old-snapshots.sh
# Add to crontab: 0 3 * * * /path/to/cleanup-old-snapshots.sh

# Clean up scheduled snapshots older than 30 days
php artisan snapshot:clear --event=scheduled --older-than=30 --confirm

# Clean up manual snapshots older than 90 days  
php artisan snapshot:clear --event=manual --older-than=90 --confirm

# Keep automatic snapshots for 14 days only
php artisan snapshot:clear --event=created --older-than=14 --confirm
php artisan snapshot:clear --event=updated --older-than=14 --confirm
php artisan snapshot:clear --event=deleted --older-than=14 --confirm
BASH;

echo $cleanupScript . "\n\n";

// Error handling and monitoring
echo "8. Error handling and monitoring:\n";

$monitoringScript = <<<'BASH'
#!/bin/bash
# snapshot-health-check.sh
# Add to crontab: */15 * * * * /path/to/snapshot-health-check.sh

# Check if scheduled snapshots are running
RECENT_COUNT=$(php artisan snapshot:list --event=scheduled --format=json | jq '[.[] | select(.created_at > (now - 86400))] | length')

if [ "$RECENT_COUNT" -eq 0 ]; then
    echo "ERROR: No scheduled snapshots in last 24 hours"
    # Send alert email
    mail -s "Snapshot Alert: No scheduled snapshots" admin@example.com <<< "No scheduled snapshots were created in the last 24 hours. Please check the scheduler."
fi

# Check for failed snapshot operations
DISK_USAGE=$(df -h /path/to/snapshot/storage | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 90 ]; then
    echo "WARNING: Snapshot storage is $DISK_USAGE% full"
    # Trigger cleanup
    php artisan snapshot:clear --older-than=7 --confirm
fi
BASH;

echo $monitoringScript . "\n\n";

// Backup and archival strategy
echo "9. Backup and archival strategy:\n";

$backupStrategy = <<<'BASH'
#!/bin/bash
# backup-snapshots.sh
# Daily backup of critical snapshots

DATE=$(date +%Y%m%d)
BACKUP_DIR="/backups/snapshots/$DATE"
mkdir -p "$BACKUP_DIR"

# Export critical model snapshots
php artisan snapshot:list --model="App\Models\Transaction" --format=json > "$BACKUP_DIR/transactions.json"
php artisan snapshot:list --model="App\Models\Payment" --format=json > "$BACKUP_DIR/payments.json"

# Compress and store
tar -czf "$BACKUP_DIR.tar.gz" "$BACKUP_DIR"
rm -rf "$BACKUP_DIR"

# Upload to S3 or other archive storage
# aws s3 cp "$BACKUP_DIR.tar.gz" s3://your-backup-bucket/snapshots/

echo "Backup completed: $BACKUP_DIR.tar.gz"
BASH;

echo $backupStrategy . "\n\n";

// Performance optimization for high-volume scheduled snapshots
echo "10. Performance optimization tips:\n";

echo "    - Use database storage for frequent queries\n";
echo "    - Use file storage for long-term archival\n";
echo "    - Limit snapshot frequency for high-volume models\n";
echo "    - Use --limit parameter to batch large datasets\n";
echo "    - Run snapshots during off-peak hours\n";
echo "    - Monitor disk space and implement cleanup\n";
echo "    - Use background processing for large batches\n";
echo "    - Consider compression for file-based storage\n\n";

// Clean up test data
echo "11. Cleaning up test data...\n";
$deletedSnapshots = Snapshot::clear(User::class);
echo "    Deleted {$deletedSnapshots} test snapshots\n";

User::whereIn('id', $users->pluck('id'))->delete();
echo "    Deleted test users\n";

echo "\n=== Scheduled Snapshots Example Complete ===\n";
echo "\nThis example demonstrates:\n";
echo "- Manual scheduled snapshot creation\n";
echo "- Console command usage for automation\n";
echo "- Laravel Scheduler integration\n";
echo "- Configuration management\n";
echo "- Monitoring and health checks\n";
echo "- Backup and archival strategies\n";
echo "- Performance optimization techniques\n";