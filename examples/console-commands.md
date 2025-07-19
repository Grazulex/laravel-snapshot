# Console Commands Examples

This document provides practical examples of using Laravel Snapshot console commands.

## Basic Command Usage

### Creating Snapshots

```bash
# Basic snapshot creation
php artisan snapshot:save "App\Models\User" --id=1 --label=user-before-update

# Auto-generated label
php artisan snapshot:save "App\Models\User" --id=1

# Multiple snapshots for testing
php artisan snapshot:save "App\Models\Order" --id=123 --label=order-received
php artisan snapshot:save "App\Models\Order" --id=123 --label=order-processing  
php artisan snapshot:save "App\Models\Order" --id=123 --label=order-completed
```

### Listing Snapshots

```bash
# List all snapshots
php artisan snapshot:list

# List with specific filters
php artisan snapshot:list --model="App\Models\User"
php artisan snapshot:list --event=manual
php artisan snapshot:list --limit=10

# JSON output for scripting
php artisan snapshot:list --format=json --model="App\Models\User" --id=1
```

### Comparing Snapshots

```bash
# Basic comparison
php artisan snapshot:diff user-before-update user-after-update

# Order processing comparison
php artisan snapshot:diff order-received order-completed
```

### Generating Reports

```bash
# HTML report
php artisan snapshot:report --model="App\Models\User" --id=1

# Save to file
php artisan snapshot:report --model="App\Models\User" --id=1 --format=html --output=user_report.html

# JSON for API consumption
php artisan snapshot:report --model="App\Models\Order" --id=123 --format=json
```

### Cleanup

```bash
# Clear all snapshots (with confirmation)
php artisan snapshot:clear

# Clear specific model snapshots
php artisan snapshot:clear --model="App\Models\User"

# Clear old snapshots
php artisan snapshot:clear --older-than=30

# Dry run to see what would be deleted
php artisan snapshot:clear --older-than=7 --dry-run
```

## Scripting Examples

### Deployment Script

```bash
#!/bin/bash
# deployment-with-snapshots.sh

echo "Starting deployment with snapshot safety..."

# Create pre-deployment snapshots of critical models
echo "Creating pre-deployment snapshots..."

php artisan snapshot:save "App\Models\Config" --id=1 --label=pre-deploy-config
php artisan snapshot:save "App\Models\Settings" --id=1 --label=pre-deploy-settings

# Run deployment steps
echo "Running deployment..."
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# Create post-deployment snapshots
echo "Creating post-deployment snapshots..."

php artisan snapshot:save "App\Models\Config" --id=1 --label=post-deploy-config
php artisan snapshot:save "App\Models\Settings" --id=1 --label=post-deploy-settings

# Compare changes
echo "Comparing deployment changes..."

php artisan snapshot:diff pre-deploy-config post-deploy-config
php artisan snapshot:diff pre-deploy-settings post-deploy-settings

echo "Deployment completed with snapshot verification!"
```

### Backup Script

```bash
#!/bin/bash
# daily-snapshot-backup.sh

DATE=$(date +%Y%m%d)
BACKUP_DIR="/backups/snapshots/$DATE"

echo "Creating daily snapshot backup for $DATE..."

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Generate reports for all active users
USERS=$(php artisan tinker --execute="echo App\\Models\\User::where('active', true)->pluck('id')->implode(' ');")

for user_id in $USERS; do
    echo "Generating report for user $user_id..."
    
    php artisan snapshot:report \
        --model="App\Models\User" \
        --id="$user_id" \
        --format=json \
        --output="$BACKUP_DIR/user_${user_id}_report.json"
done

# Generate system-wide statistics
php artisan snapshot:list --format=json --output="$BACKUP_DIR/all_snapshots.json"

echo "Backup completed: $BACKUP_DIR"
```

### Monitoring Script

```bash
#!/bin/bash
# snapshot-monitoring.sh

# Check for failed snapshots or unusual activity
RECENT_COUNT=$(php artisan snapshot:list --format=json | jq '[.[] | select(.created_at > (now - 86400))] | length')

if [ "$RECENT_COUNT" -gt 1000 ]; then
    echo "WARNING: High snapshot activity detected ($RECENT_COUNT snapshots in last 24h)"
    
    # Generate analysis report
    php artisan snapshot:report --format=json --output=/tmp/snapshot_analysis.json
    
    # Send alert email (requires mail setup)
    # mail -s "High Snapshot Activity Alert" admin@example.com < /tmp/snapshot_analysis.json
fi

# Check for old snapshots that should be cleaned
OLD_COUNT=$(php artisan snapshot:clear --older-than=90 --dry-run | grep "Total:" | cut -d' ' -f2)

if [ "$OLD_COUNT" -gt 0 ]; then
    echo "Found $OLD_COUNT old snapshots ready for cleanup"
    
    # Actually clean them up
    php artisan snapshot:clear --older-than=90 --confirm
    
    echo "Cleaned up $OLD_COUNT old snapshots"
fi
```

## Automation Examples

### Cron Jobs

```bash
# Add to crontab: crontab -e

# Daily cleanup of old snapshots
0 2 * * * cd /path/to/app && php artisan snapshot:clear --older-than=30 --confirm

# Weekly comprehensive reports
0 1 * * 0 cd /path/to/app && /path/to/scripts/weekly-snapshot-reports.sh

# Hourly monitoring
0 * * * * cd /path/to/app && /path/to/scripts/snapshot-monitoring.sh
```

### Laravel Scheduler Integration

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Daily snapshot cleanup
    $schedule->command('snapshot:clear', ['--older-than=30', '--confirm'])
             ->daily()
             ->at('02:00');
    
    // Weekly reports for active users
    $schedule->call(function () {
        $activeUsers = User::where('active', true)->get();
        
        foreach ($activeUsers as $user) {
            $this->call('snapshot:report', [
                '--model' => 'App\Models\User',
                '--id' => $user->id,
                '--format' => 'html',
                '--output' => storage_path("reports/weekly/user_{$user->id}_" . date('Y_W') . ".html")
            ]);
        }
    })->weekly();
    
    // Monthly statistics
    $schedule->command('snapshot:list', ['--format=json', '--output=storage/stats/monthly_' . date('Y_m') . '.json'])
             ->monthly();
}
```

## Advanced Command Examples

### Batch Processing

```bash
# Process multiple models in batch
for model in "User" "Order" "Payment" "Product"; do
    echo "Processing $model snapshots..."
    
    php artisan snapshot:list --model="App\\Models\\$model" --format=json > "/tmp/${model}_snapshots.json"
    
    # Process each model's snapshots
    COUNT=$(cat "/tmp/${model}_snapshots.json" | jq 'length')
    echo "$model has $COUNT snapshots"
done
```

### Conditional Snapshots

```bash
#!/bin/bash
# conditional-snapshots.sh

# Only create snapshots if model has changed recently
USER_ID=1
LAST_SNAPSHOT_DATE=$(php artisan snapshot:list --model="App\\Models\\User" --format=json | jq -r ".[0].created_at // empty")
LAST_USER_UPDATE=$(php artisan tinker --execute="echo App\\Models\\User::find($USER_ID)->updated_at;")

if [[ "$LAST_USER_UPDATE" > "$LAST_SNAPSHOT_DATE" ]]; then
    echo "User has been updated since last snapshot, creating new snapshot..."
    php artisan snapshot:save "App\\Models\\User" --id="$USER_ID" --label="auto-$(date +%Y%m%d_%H%M%S)"
else
    echo "User hasn't changed since last snapshot, skipping..."
fi
```

### Error Handling

```bash
#!/bin/bash
# robust-snapshot-script.sh

set -e  # Exit on error

cleanup() {
    echo "Script interrupted, cleaning up..."
    # Add any cleanup code here
}

trap cleanup INT TERM

# Function to handle snapshot operations safely
create_snapshot_safe() {
    local model="$1"
    local id="$2" 
    local label="$3"
    
    echo "Creating snapshot: $label"
    
    if php artisan snapshot:save "$model" --id="$id" --label="$label" 2>/dev/null; then
        echo "✓ Snapshot created successfully: $label"
        return 0
    else
        echo "✗ Failed to create snapshot: $label"
        return 1
    fi
}

# Usage with error handling
if create_snapshot_safe "App\\Models\\User" "1" "user-$(date +%Y%m%d)"; then
    echo "Proceeding with next steps..."
else
    echo "Snapshot creation failed, aborting script"
    exit 1
fi
```

## Integration Examples

### CI/CD Pipeline Integration

```yaml
# .github/workflows/deployment.yml

- name: Create pre-deployment snapshots
  run: |
    php artisan snapshot:save "App\Models\Config" --id=1 --label="deploy-${{ github.sha }}-pre"
    php artisan snapshot:save "App\Models\Settings" --id=1 --label="deploy-${{ github.sha }}-pre"

- name: Run deployment
  run: |
    php artisan migrate --force
    php artisan config:cache

- name: Create post-deployment snapshots
  run: |
    php artisan snapshot:save "App\Models\Config" --id=1 --label="deploy-${{ github.sha }}-post"
    php artisan snapshot:save "App\Models\Settings" --id=1 --label="deploy-${{ github.sha }}-post"

- name: Verify deployment changes
  run: |
    php artisan snapshot:diff "deploy-${{ github.sha }}-pre" "deploy-${{ github.sha }}-post"
```

### Docker Integration

```dockerfile
# Dockerfile with snapshot support

FROM php:8.3-fpm

# ... other setup ...

# Add snapshot management script
COPY scripts/snapshot-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/snapshot-entrypoint.sh

# Set as entrypoint for snapshot-aware container startup
ENTRYPOINT ["/usr/local/bin/snapshot-entrypoint.sh"]
```

```bash
#!/bin/bash
# snapshot-entrypoint.sh

# Create startup snapshot
php artisan snapshot:save "App\\Models\\Config" --id=1 --label="container-start-$(date +%s)"

# Run the main application
exec "$@"
```

## Performance Monitoring

### Command Performance Analysis

```bash
#!/bin/bash
# performance-analysis.sh

# Test snapshot creation performance
echo "Testing snapshot creation performance..."

time_start=$(date +%s.%N)
php artisan snapshot:save "App\\Models\\User" --id=1 --label="perf-test-$(date +%s)"
time_end=$(date +%s.%N)

creation_time=$(echo "$time_end - $time_start" | bc)
echo "Snapshot creation took: ${creation_time}s"

# Test diff performance
echo "Testing diff performance..."

time_start=$(date +%s.%N)
php artisan snapshot:diff "snapshot1" "snapshot2" > /dev/null 2>&1
time_end=$(date +%s.%N)

diff_time=$(echo "$time_end - $time_start" | bc)
echo "Snapshot diff took: ${diff_time}s"
```

### Resource Usage Monitoring

```bash
#!/bin/bash
# monitor-snapshot-resources.sh

# Monitor memory usage during snapshot operations
monitor_memory() {
    local pid=$1
    while kill -0 "$pid" 2>/dev/null; do
        ps -o pid,vsz,rss -p "$pid"
        sleep 1
    done
}

echo "Starting resource monitoring for snapshot operations..."

# Run snapshot command in background
php artisan snapshot:save "App\\Models\\LargeModel" --id=1 --label="memory-test" &
SNAPSHOT_PID=$!

# Monitor its resource usage
monitor_memory $SNAPSHOT_PID &
MONITOR_PID=$!

# Wait for snapshot to complete
wait $SNAPSHOT_PID

# Stop monitoring
kill $MONITOR_PID 2>/dev/null

echo "Snapshot operation completed"
```