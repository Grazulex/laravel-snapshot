# Reports & Analytics  

Laravel Snapshot provides comprehensive reporting and analytics features to help you understand how your data changes over time.

## Overview

The reporting system offers:
- **Timeline Reports** - Chronological view of model changes
- **Statistics & Analytics** - Change patterns and frequency analysis  
- **History Reports** - Comprehensive model history in multiple formats
- **Diff Analysis** - Detailed comparisons between any snapshots
- **Change Metrics** - Quantitative analysis of model modifications

## Report Generation

### HTML Reports

Generate rich HTML reports with timeline, statistics, and visual diffs:

```php
$user = User::find(1);

// Generate HTML report
$htmlReport = $user->getHistoryReport('html');

// Save to file
file_put_contents('user_history.html', $htmlReport);

// Or return in controller
return response($htmlReport)
    ->header('Content-Type', 'text/html');
```

**HTML Report Features:**
- Interactive timeline with collapsible entries
- Color-coded diff highlighting  
- Statistics dashboard
- Export links for other formats
- Responsive design for mobile viewing

### JSON Reports

Generate structured JSON reports for API consumption:

```php
$jsonReport = $user->getHistoryReport('json');
$reportData = json_decode($jsonReport, true);

/*
{
    "model": {
        "type": "App\\Models\\User",
        "id": 1,
        "current_state": {...}
    },
    "statistics": {
        "total_snapshots": 15,
        "snapshots_by_event": {...},
        "most_changed_fields": [...],
        "change_frequency": {...}
    },
    "timeline": [
        {
            "id": 123,
            "label": "user-updated-profile",
            "event_type": "updated", 
            "created_at": "2024-07-19T10:30:00Z",
            "changes": {
                "modified": {
                    "name": {"from": "John", "to": "John Doe"}
                }
            }
        }
    ],
    "generated_at": "2024-07-19T15:00:00Z"
}
*/
```

### CSV Reports

Generate CSV reports for spreadsheet analysis:

```php
$csvReport = $user->getHistoryReport('csv');

// Save to file
file_put_contents('user_history.csv', $csvReport);

// Or download in controller
return response($csvReport)
    ->header('Content-Type', 'text/csv')
    ->header('Content-Disposition', 'attachment; filename=user_history.csv');
```

**CSV Format:**
```csv
timestamp,event_type,field_name,old_value,new_value,snapshot_label
2024-07-19 10:30:00,updated,name,"John","John Doe",user-updated-profile
2024-07-19 10:30:00,updated,email,"john@old.com","john@new.com",user-updated-profile
2024-07-19 09:00:00,created,name,,John,user-created
```

## Console Reports

Generate reports via Artisan commands:

```bash
# Generate HTML report for a user
php artisan snapshot:report --model="App\Models\User" --id=1 --format=html

# Generate JSON report and save to file
php artisan snapshot:report --model="App\Models\User" --id=1 --format=json --output=user_report.json

# Generate CSV report for an order
php artisan snapshot:report --model="App\Models\Order" --id=123 --format=csv --output=order_history.csv
```

## Statistics & Analytics

### Basic Statistics

Get comprehensive statistics for models:

```php
// Global statistics
$globalStats = Snapshot::stats()
    ->counters()
    ->changeFrequency()
    ->get();

// Model-specific statistics  
$user = User::find(1);
$userStats = Snapshot::stats($user)
    ->counters()
    ->mostChangedFields()
    ->changeFrequency()
    ->get();

/*
Results:
[
    'total_snapshots' => 15,
    'snapshots_by_event' => [
        'manual' => 5,
        'created' => 1,
        'updated' => 9
    ],
    'most_changed_fields' => [
        'name' => 8,
        'email' => 5,
        'role' => 2
    ],
    'changes_by_day' => [
        '2024-07-19' => 3,
        '2024-07-18' => 2,
        '2024-07-17' => 5
    ]
]
*/
```

### Advanced Analytics

#### Change Frequency Analysis

```php
// Analyze change patterns over time
$stats = Snapshot::stats($user)->changeFrequency()->get();

$changesByDay = $stats['changes_by_day'];
$changesByWeek = $stats['changes_by_week'] ?? [];
$changesByMonth = $stats['changes_by_month'] ?? [];

// Find peak activity periods
$busiestDay = array_keys($changesByDay, max($changesByDay))[0];
echo "Busiest day: {$busiestDay} with " . max($changesByDay) . " changes";
```

#### Field Change Analysis

```php
// Custom analysis of field changes
$snapshots = $user->snapshots()->get();

$fieldChanges = [];
foreach ($snapshots as $snapshot) {
    $data = json_decode($snapshot->data, true);
    
    // Compare with previous snapshot to identify changed fields
    // (Implementation depends on your specific needs)
}

// Most frequently changed fields
arsort($fieldChanges);
$topChangedFields = array_slice($fieldChanges, 0, 5, true);
```

#### Model Activity Metrics

```php
use Grazulex\LaravelSnapshot\Models\ModelSnapshot;

// Most active models
$modelActivity = ModelSnapshot::selectRaw('model_type, COUNT(*) as snapshot_count')
    ->where('created_at', '>=', now()->subDays(30))
    ->groupBy('model_type')  
    ->orderBy('snapshot_count', 'desc')
    ->limit(10)
    ->get();

// Activity by event type
$eventActivity = ModelSnapshot::selectRaw('event_type, COUNT(*) as count')
    ->where('created_at', '>=', now()->subDays(30))
    ->groupBy('event_type')
    ->get();
```

## Timeline Analysis

### Model Timeline

Get detailed timeline with analysis:

```php
$user = User::find(1);

// Get timeline with metadata
$timeline = $user->getSnapshotTimeline(50);

foreach ($timeline as $entry) {
    echo "Snapshot: {$entry['label']}\n";
    echo "Event: {$entry['event_type']}\n";
    echo "Date: {$entry['created_at']}\n";
    
    // Analyze the data if needed
    $data = $entry['data'];
    $attributes = $data['attributes'] ?? [];
    
    echo "Fields: " . implode(', ', array_keys($attributes)) . "\n\n";
}
```

### Change Timeline

Generate timeline showing only changes:

```php
$snapshots = $user->snapshots()->orderBy('created_at', 'asc')->get();
$changeTimeline = [];

for ($i = 1; $i < count($snapshots); $i++) {
    $previous = json_decode($snapshots[$i - 1]->data, true);
    $current = json_decode($snapshots[$i]->data, true);
    
    $diff = Snapshot::calculateDiff($previous, $current);
    
    if (!empty($diff['modified']) || !empty($diff['added']) || !empty($diff['removed'])) {
        $changeTimeline[] = [
            'timestamp' => $snapshots[$i]->created_at,
            'snapshot_label' => $snapshots[$i]->label,
            'changes' => $diff
        ];
    }
}

// Now $changeTimeline contains only snapshots with actual changes
```

## Custom Reports

### Creating Custom Report Classes

```php
<?php

namespace App\Reports;

use Grazulex\LaravelSnapshot\Models\ModelSnapshot;

class UserActivityReport
{
    public function generate($userId, string $format = 'html'): string
    {
        $snapshots = ModelSnapshot::where('model_type', 'App\Models\User')
            ->where('model_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
            
        $data = $this->analyzeSnapshots($snapshots);
        
        return match($format) {
            'html' => $this->generateHtml($data),
            'json' => $this->generateJson($data),
            'csv' => $this->generateCsv($data),
            default => throw new InvalidArgumentException("Unsupported format: {$format}")
        };
    }
    
    private function analyzeSnapshots($snapshots): array
    {
        $analysis = [
            'total_snapshots' => $snapshots->count(),
            'date_range' => [
                'first' => $snapshots->last()->created_at ?? null,
                'last' => $snapshots->first()->created_at ?? null,
            ],
            'events' => $snapshots->groupBy('event_type')->map->count(),
            'activity_by_day' => [],
            'field_changes' => [],
        ];
        
        // Add custom analysis logic here
        
        return $analysis;
    }
    
    private function generateHtml(array $data): string
    {
        // Generate HTML report
        return view('reports.user_activity', compact('data'))->render();
    }
    
    private function generateJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
    
    private function generateCsv(array $data): string
    {
        // Generate CSV format
        $csv = "Date,Event Type,Changes\n";
        // Add CSV generation logic
        return $csv;
    }
}
```

Usage:
```php
$report = new UserActivityReport();
$htmlReport = $report->generate(1, 'html');
```

### Report Templates

Create Blade templates for HTML reports:

```blade
{{-- resources/views/reports/user_activity.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>User Activity Report</title>
    <style>
        .timeline { border-left: 2px solid #007cba; padding-left: 20px; }
        .timeline-item { margin-bottom: 20px; }
        .diff-added { background-color: #d4edda; }
        .diff-modified { background-color: #fff3cd; }
        .diff-removed { background-color: #f8d7da; }
    </style>
</head>
<body>
    <h1>User Activity Report</h1>
    
    <div class="stats">
        <h2>Statistics</h2>
        <p>Total Snapshots: {{ $data['total_snapshots'] }}</p>
        <p>Date Range: {{ $data['date_range']['first'] }} to {{ $data['date_range']['last'] }}</p>
        
        <h3>Events</h3>
        @foreach($data['events'] as $event => $count)
            <p>{{ ucfirst($event) }}: {{ $count }}</p>
        @endforeach
    </div>
    
    <div class="timeline">
        <h2>Timeline</h2>
        {{-- Timeline items would go here --}}
    </div>
</body>
</html>
```

## Scheduled Reports

### Daily Reports

Set up automatic report generation:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Generate daily user activity reports
    $schedule->call(function () {
        $activeUsers = User::where('active', true)->get();
        
        foreach ($activeUsers as $user) {
            $report = $user->getHistoryReport('html');
            Storage::put("reports/daily/user-{$user->id}-" . date('Y-m-d') . ".html", $report);
        }
    })->daily();
    
    // Generate weekly summary reports
    $schedule->call(function () {
        $weeklyStats = Snapshot::stats()
            ->counters()
            ->changeFrequency()
            ->get();
            
        Storage::put("reports/weekly/summary-" . date('Y-W') . ".json", json_encode($weeklyStats));
    })->weekly();
}
```

### Email Reports

Send reports via email:

```php
use Illuminate\Mail\Mailable;

class SnapshotReport extends Mailable
{
    public function __construct(private $user, private string $reportHtml)
    {
    }
    
    public function build()
    {
        return $this->view('emails.snapshot-report')
                    ->with(['reportHtml' => $this->reportHtml])
                    ->subject("Weekly Activity Report for {$this->user->name}");
    }
}

// Usage
$user = User::find(1);
$report = $user->getHistoryReport('html');
Mail::to($user)->send(new SnapshotReport($user, $report));
```

## Performance Considerations

### Large Datasets

For models with many snapshots:

```php
// Limit report scope
$recentSnapshots = $user->snapshots()
    ->where('created_at', '>=', now()->subDays(30))
    ->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();

// Generate report from limited data
$report = new CustomReport($recentSnapshots);
```

### Caching Reports

Cache expensive reports:

```php
public function getUserReport($userId)
{
    $cacheKey = "user_report_{$userId}_" . date('Y-m-d');
    
    return Cache::remember($cacheKey, 3600, function () use ($userId) {
        $user = User::find($userId);
        return $user->getHistoryReport('html');
    });
}
```

### Background Processing

Generate large reports in background jobs:

```php
// Create a job
php artisan make:job GenerateUserReport

// Job class
class GenerateUserReport implements ShouldQueue
{
    public function handle()
    {
        $report = $this->user->getHistoryReport('html');
        Storage::put("reports/user-{$this->user->id}.html", $report);
        
        // Notify user via email or notification
        Mail::to($this->user)->send(new ReportGenerated($report));
    }
}

// Dispatch the job
GenerateUserReport::dispatch($user);
```

## Report Configuration

Configure report generation in `config/snapshot.php`:

```php
'reports' => [
    'enabled' => true,
    'formats' => ['html', 'json', 'csv'],
    'template' => 'default',
    'max_timeline_entries' => 100,
    'include_diffs' => true,
    'cache_duration' => 3600, // Cache reports for 1 hour
    'background_generation' => true, // Use queues for large reports
],
```

## API Endpoints

Create API endpoints for reports:

```php
// routes/api.php
Route::get('/users/{user}/snapshots/report', function (User $user, Request $request) {
    $format = $request->get('format', 'json');
    
    $report = $user->getHistoryReport($format);
    
    return response($report)
        ->header('Content-Type', match($format) {
            'json' => 'application/json',
            'csv' => 'text/csv',  
            'html' => 'text/html',
        });
});

// Usage: GET /api/users/1/snapshots/report?format=json
```

## Next Steps

- [Advanced Usage](advanced-usage.md) - Performance optimization and advanced patterns
- [Console Commands](console-commands.md) - CLI report generation  
- [API Reference](api-reference.md) - Complete reporting API