<?php

declare(strict_types=1);
/**
 * Example: Scheduled Snapshots
 * Description: Periodic automatic snapshots for regular monitoring and backups
 *
 * Prerequisites:
 * - Laravel Snapshot package configured
 * - Cron/scheduler setup
 * - Models to snapshot
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/scheduled-snapshots.php';
 */

use App\Models\User;
use Grazulex\LaravelSnapshot\Snapshot;

echo "=== Scheduled Snapshots Example ===\n\n";

// Mock scheduler class to demonstrate scheduling
class SnapshotScheduler
{
    private array $schedules = [];
    
    public function addSchedule(string $modelClass, string $frequency, array $options = []): void
    {
        $this->schedules[] = [
            'model' => $modelClass,
            'frequency' => $frequency,
            'options' => $options,
            'last_run' => null,
            'next_run' => $this->calculateNextRun($frequency),
        ];
    }
    
    public function getSchedules(): array
    {
        return $this->schedules;
    }
    
    public function runSchedule(int $index): array
    {
        if (!isset($this->schedules[$index])) {
            return ['error' => 'Schedule not found'];
        }
        
        $schedule = $this->schedules[$index];
        $results = [];
        
        // Mock running the scheduled snapshot
        $modelClass = $schedule['model'];
        $frequency = $schedule['frequency'];
        $options = $schedule['options'];
        
        // Create multiple snapshots to simulate multiple model instances
        for ($i = 1; $i <= ($options['limit'] ?? 3); $i++) {
            $mockData = [
                'id' => $i,
                'type' => $modelClass,
                'scheduled_at' => date('Y-m-d H:i:s'),
                'frequency' => $frequency,
                'data' => ['example' => 'data', 'counter' => rand(1, 100)],
            ];
            
            $label = "scheduled-{$frequency}-{$modelClass}-{$i}-" . date('Y-m-d-H-i-s');
            $snapshot = Snapshot::save($mockData, $label);
            $results[] = $label;
        }
        
        // Update schedule
        $this->schedules[$index]['last_run'] = date('Y-m-d H:i:s');
        $this->schedules[$index]['next_run'] = $this->calculateNextRun($frequency);
        
        return [
            'success' => true,
            'snapshots_created' => count($results),
            'labels' => $results,
        ];
    }
    
    private function calculateNextRun(string $frequency): string
    {
        $intervals = [
            'hourly' => '+1 hour',
            'daily' => '+1 day',
            'weekly' => '+1 week',
            'monthly' => '+1 month',
        ];
        
        return date('Y-m-d H:i:s', strtotime($intervals[$frequency] ?? '+1 day'));
    }
}

// 1. Setting up scheduled snapshots
echo "1. Setting up scheduled snapshots:\n";
$scheduler = new SnapshotScheduler();

// Daily snapshots for critical models
$scheduler->addSchedule('User', 'daily', [
    'limit' => 5,
    'label_prefix' => 'daily-backup',
    'retention_days' => 30,
]);
echo "   ✓ Daily User snapshots scheduled\n";

// Hourly snapshots for frequently changing data
$scheduler->addSchedule('Order', 'hourly', [
    'limit' => 10,
    'label_prefix' => 'hourly-monitor',
    'retention_days' => 7,
]);
echo "   ✓ Hourly Order snapshots scheduled\n";

// Weekly snapshots for configuration
$scheduler->addSchedule('Config', 'weekly', [
    'limit' => 1,
    'label_prefix' => 'weekly-config',
    'retention_days' => 90,
]);
echo "   ✓ Weekly Config snapshots scheduled\n";

// Monthly snapshots for archival
$scheduler->addSchedule('Report', 'monthly', [
    'limit' => 2,
    'label_prefix' => 'monthly-archive',
    'retention_days' => 365,
]);
echo "   ✓ Monthly Report snapshots scheduled\n";

// 2. Display schedule configuration
echo "\n2. Schedule configuration:\n";
foreach ($scheduler->getSchedules() as $index => $schedule) {
    echo "   Schedule #{$index}:\n";
    echo "     - Model: {$schedule['model']}\n";
    echo "     - Frequency: {$schedule['frequency']}\n";
    echo "     - Limit: {$schedule['options']['limit']}\n";
    echo "     - Next run: {$schedule['next_run']}\n";
}

// 3. Simulate running scheduled snapshots
echo "\n3. Simulating scheduled snapshot execution:\n";

// Run daily user snapshots
echo "   Running daily User snapshots:\n";
$dailyResult = $scheduler->runSchedule(0);
if ($dailyResult['success']) {
    echo "     ✓ Created {$dailyResult['snapshots_created']} snapshots\n";
    foreach ($dailyResult['labels'] as $label) {
        echo "       - {$label}\n";
    }
}

sleep(1);

// Run hourly order snapshots
echo "\n   Running hourly Order snapshots:\n";
$hourlyResult = $scheduler->runSchedule(1);
if ($hourlyResult['success']) {
    echo "     ✓ Created {$hourlyResult['snapshots_created']} snapshots\n";
    foreach ($hourlyResult['labels'] as $label) {
        echo "       - {$label}\n";
    }
}

sleep(1);

// Run weekly config snapshots
echo "\n   Running weekly Config snapshots:\n";
$weeklyResult = $scheduler->runSchedule(2);
if ($weeklyResult['success']) {
    echo "     ✓ Created {$weeklyResult['snapshots_created']} snapshots\n";
    foreach ($weeklyResult['labels'] as $label) {
        echo "       - {$label}\n";
    }
}

// 4. Analyze scheduled snapshots
echo "\n4. Analyzing scheduled snapshots:\n";
$allSnapshots = Snapshot::list();
$scheduledSnapshots = array_filter($allSnapshots, function($snapshot, $label) {
    return strpos($label, 'scheduled-') === 0;
}, ARRAY_FILTER_USE_BOTH);

echo "   Total scheduled snapshots: " . count($scheduledSnapshots) . "\n";

// Group by frequency
$byFrequency = [];
foreach ($scheduledSnapshots as $label => $snapshot) {
    if (preg_match('/scheduled-(\w+)-/', $label, $matches)) {
        $frequency = $matches[1];
        $byFrequency[$frequency] = ($byFrequency[$frequency] ?? 0) + 1;
    }
}

echo "   Snapshots by frequency:\n";
foreach ($byFrequency as $frequency => $count) {
    echo "     - {$frequency}: {$count} snapshots\n";
}

// 5. Retention policy demonstration
echo "\n5. Retention policy management:\n";

class RetentionManager
{
    public static function applyRetention(array $snapshots, int $retentionDays): array
    {
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        $toDelete = [];
        $toKeep = [];
        
        foreach ($snapshots as $label => $snapshot) {
            $snapshotTime = strtotime($snapshot['timestamp'] ?? 'now');
            if ($snapshotTime < $cutoffTime) {
                $toDelete[] = $label;
            } else {
                $toKeep[] = $label;
            }
        }
        
        return ['delete' => $toDelete, 'keep' => $toKeep];
    }
}

// Simulate old snapshots for retention testing
$oldSnapshots = [];
for ($i = 1; $i <= 5; $i++) {
    $oldLabel = "scheduled-daily-OldModel-{$i}-" . date('Y-m-d-H-i-s', strtotime("-35 days"));
    $oldData = ['id' => $i, 'old' => true, 'created_days_ago' => 35];
    Snapshot::save($oldData, $oldLabel);
    $oldSnapshots[$oldLabel] = ['timestamp' => date('Y-m-d H:i:s', strtotime("-35 days"))];
}

// Apply 30-day retention policy
$retention = RetentionManager::applyRetention($oldSnapshots, 30);
echo "   Retention policy (30 days) analysis:\n";
echo "     - Snapshots to delete: " . count($retention['delete']) . "\n";
echo "     - Snapshots to keep: " . count($retention['keep']) . "\n";

// Clean up old snapshots
foreach ($retention['delete'] as $label) {
    Snapshot::delete($label);
    echo "     ✓ Deleted expired snapshot: {$label}\n";
}

// 6. Laravel Scheduler Integration
echo "\n6. Laravel Scheduler Integration Examples:\n";

$laravelSchedulerExample = <<<'PHP'
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Daily snapshots at 2 AM
    $schedule->command('snapshot:schedule App\\Models\\User --label=daily-backup')
             ->daily()
             ->at('02:00');
    
    // Hourly snapshots for active orders
    $schedule->command('snapshot:schedule App\\Models\\Order --label=hourly-monitor --limit=100')
             ->hourly();
    
    // Weekly configuration snapshots
    $schedule->command('snapshot:schedule App\\Models\\Config --label=weekly-config')
             ->weekly()
             ->sundays()
             ->at('01:00');
    
    // Monthly archival snapshots
    $schedule->command('snapshot:schedule App\\Models\\Report --label=monthly-archive')
             ->monthlyOn(1, '00:30');
    
    // Retention cleanup - daily at 3 AM
    $schedule->command('snapshot:clear --before=' . date('Y-m-d', strtotime('-30 days')))
             ->daily()
             ->at('03:00');
}

PHP;

echo $laravelSchedulerExample;

// 7. Monitoring and alerting
echo "\n7. Monitoring scheduled snapshots:\n";

class SnapshotMonitor
{
    public static function checkHealth(array $schedules): array
    {
        $health = [];
        
        foreach ($schedules as $index => $schedule) {
            $modelClass = $schedule['model'];
            $frequency = $schedule['frequency'];
            $lastRun = $schedule['last_run'];
            $nextRun = $schedule['next_run'];
            
            $status = 'healthy';
            $message = 'Running on schedule';
            
            // Check if schedule is overdue
            if ($nextRun && strtotime($nextRun) < time()) {
                $status = 'overdue';
                $message = 'Schedule is overdue';
            }
            
            // Check if last run was too long ago
            if ($lastRun) {
                $hoursSinceLastRun = (time() - strtotime($lastRun)) / 3600;
                $maxHours = ['hourly' => 2, 'daily' => 26, 'weekly' => 168, 'monthly' => 744][$frequency] ?? 24;
                
                if ($hoursSinceLastRun > $maxHours) {
                    $status = 'stale';
                    $message = "Last run was {$hoursSinceLastRun} hours ago";
                }
            }
            
            $health[] = [
                'schedule' => "{$frequency} {$modelClass}",
                'status' => $status,
                'message' => $message,
                'last_run' => $lastRun,
                'next_run' => $nextRun,
            ];
        }
        
        return $health;
    }
}

$healthCheck = SnapshotMonitor::checkHealth($scheduler->getSchedules());
echo "   Schedule health status:\n";
foreach ($healthCheck as $check) {
    $status = $check['status'];
    $emoji = ['healthy' => '✓', 'overdue' => '⚠', 'stale' => '✗'][$status] ?? '?';
    echo "     {$emoji} {$check['schedule']}: {$check['message']}\n";
}

// 8. Performance optimization for scheduled snapshots
echo "\n8. Performance optimization tips:\n";
echo "   ✓ Batch snapshots during off-peak hours\n";
echo "   ✓ Use database indexes on model_type and created_at columns\n";
echo "   ✓ Implement parallel processing for large datasets\n";
echo "   ✓ Use queue workers for non-blocking snapshot creation\n";
echo "   ✓ Monitor storage usage and implement compression\n";
echo "   ✓ Set appropriate retention policies\n";
echo "   ✓ Use database partitioning for large snapshot tables\n";

// 9. Configuration examples
echo "\n9. Configuration for scheduled snapshots:\n";

$configExample = <<<'PHP'
// config/snapshot.php
'scheduled' => [
    'enabled' => env('SNAPSHOT_SCHEDULED_ENABLED', true),
    'models' => [
        'App\\Models\\User' => [
            'frequency' => 'daily',
            'time' => '02:00',
            'limit' => 1000,
            'retention_days' => 30,
        ],
        'App\\Models\\Order' => [
            'frequency' => 'hourly', 
            'limit' => 500,
            'retention_days' => 7,
            'conditions' => ['status' => 'active'],
        ],
        'App\\Models\\Config' => [
            'frequency' => 'weekly',
            'day' => 'sunday',
            'time' => '01:00',
            'retention_days' => 90,
        ],
    ],
    'storage' => [
        'driver' => 'database', // or 'file', 'cloud'
        'compress' => true,
        'encrypt' => false,
    ],
    'notifications' => [
        'on_failure' => ['mail', 'slack'],
        'on_success' => false,
        'recipients' => ['admin@example.com'],
    ],
],

PHP;

echo $configExample;

// 10. Cleanup and summary
echo "\n10. Cleanup and summary:\n";
$totalSnapshots = count(Snapshot::list());
echo "   Total snapshots before cleanup: {$totalSnapshots}\n";

$deletedCount = Snapshot::clear();
echo "   ✓ Cleaned up {$deletedCount} test snapshots\n";

echo "\n=== Scheduled Snapshots Benefits Demonstrated ===\n";
echo "✓ Automated regular backups\n";
echo "✓ Configurable retention policies\n";
echo "✓ Multiple frequency options (hourly, daily, weekly, monthly)\n";
echo "✓ Laravel Scheduler integration\n";
echo "✓ Health monitoring and alerting\n";
echo "✓ Performance optimization strategies\n";
echo "✓ Flexible configuration options\n";
echo "✓ Automated cleanup and maintenance\n";

echo "\nScheduled snapshots example completed successfully!\n";