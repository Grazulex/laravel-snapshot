<?php

declare(strict_types=1);
/**
 * Example: Report Generation
 * Description: Generate comprehensive reports from snapshot data
 *
 * Prerequisites:
 * - Laravel Snapshot package configured
 * - Models with snapshots
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/report-generation.php';
 */

use App\Models\User;
use Grazulex\LaravelSnapshot\Snapshot;

echo "=== Report Generation Example ===\n\n";

// Create sample data for reporting
echo "1. Creating sample data for reporting:\n";

// Mock user data with changes over time
$user = [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'status' => 'inactive',
    'login_count' => 0,
    'last_login' => null,
];

// Create timeline of changes
Snapshot::save($user, 'user-1-created');
echo "   ✓ User created snapshot\n";

sleep(1);
$user['status'] = 'active';
$user['login_count'] = 1;
$user['last_login'] = '2024-07-01 09:00:00';
Snapshot::save($user, 'user-1-first-login');
echo "   ✓ First login snapshot\n";

sleep(1);
$user['name'] = 'John Smith';
$user['login_count'] = 5;
$user['last_login'] = '2024-07-05 14:30:00';
Snapshot::save($user, 'user-1-name-change');
echo "   ✓ Name change snapshot\n";

sleep(1);
$user['email'] = 'john.smith@example.com';
$user['login_count'] = 12;
$user['last_login'] = '2024-07-10 11:15:00';
Snapshot::save($user, 'user-1-email-change');
echo "   ✓ Email change snapshot\n";

// Create additional users for comprehensive reporting
$user2 = ['id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'status' => 'active'];
$user3 = ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'status' => 'inactive'];

Snapshot::save($user2, 'user-2-created');
Snapshot::save($user3, 'user-3-created');
echo "   ✓ Additional user snapshots created\n";

// 2. Basic timeline report
echo "\n2. Generating timeline report:\n";

class SnapshotReportGenerator
{
    public static function generateTimelineReport(string $modelPattern = null): array
    {
        $snapshots = Snapshot::list();
        $timeline = [];
        
        foreach ($snapshots as $label => $snapshot) {
            if ($modelPattern && strpos($label, $modelPattern) === false) {
                continue;
            }
            
            $timeline[] = [
                'label' => $label,
                'timestamp' => $snapshot['timestamp'] ?? 'N/A',
                'data' => $snapshot['data'] ?? $snapshot,
            ];
        }
        
        // Sort by timestamp
        usort($timeline, function($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });
        
        return $timeline;
    }
    
    public static function generateSummaryReport(): array
    {
        $snapshots = Snapshot::list();
        $summary = [
            'total_snapshots' => count($snapshots),
            'by_model' => [],
            'by_date' => [],
            'recent_activity' => [],
        ];
        
        foreach ($snapshots as $label => $snapshot) {
            // Extract model from label (basic pattern)
            if (preg_match('/user-(\d+)/', $label, $matches)) {
                $modelKey = "User #{$matches[1]}";
                $summary['by_model'][$modelKey] = ($summary['by_model'][$modelKey] ?? 0) + 1;
            }
            
            // Group by date
            $timestamp = $snapshot['timestamp'] ?? 'Unknown';
            $date = substr($timestamp, 0, 10);
            $summary['by_date'][$date] = ($summary['by_date'][$date] ?? 0) + 1;
            
            // Recent activity (last 5)
            if (count($summary['recent_activity']) < 5) {
                $summary['recent_activity'][] = [
                    'label' => $label,
                    'timestamp' => $timestamp,
                ];
            }
        }
        
        return $summary;
    }
    
    public static function generateChangeAnalysisReport(): array
    {
        $snapshots = Snapshot::list();
        $changes = [];
        
        // Group snapshots by model
        $byModel = [];
        foreach ($snapshots as $label => $snapshot) {
            if (preg_match('/user-(\d+)/', $label, $matches)) {
                $userId = $matches[1];
                $byModel["User #{$userId}"][] = ['label' => $label, 'data' => $snapshot['data'] ?? $snapshot];
            }
        }
        
        // Analyze changes for each model
        foreach ($byModel as $modelKey => $modelSnapshots) {
            if (count($modelSnapshots) < 2) continue;
            
            $modelChanges = [];
            for ($i = 1; $i < count($modelSnapshots); $i++) {
                $before = $modelSnapshots[$i-1];
                $after = $modelSnapshots[$i];
                
                $diff = self::calculateSimpleDiff($before['data'], $after['data']);
                if (!empty($diff)) {
                    $modelChanges[] = [
                        'from' => $before['label'],
                        'to' => $after['label'],
                        'changes' => $diff,
                    ];
                }
            }
            
            if (!empty($modelChanges)) {
                $changes[$modelKey] = $modelChanges;
            }
        }
        
        return $changes;
    }
    
    private static function calculateSimpleDiff(array $before, array $after): array
    {
        $diff = [];
        
        foreach ($after as $key => $value) {
            if (!isset($before[$key])) {
                $diff['added'][$key] = $value;
            } elseif ($before[$key] !== $value) {
                $diff['modified'][$key] = [
                    'from' => $before[$key],
                    'to' => $value,
                ];
            }
        }
        
        foreach ($before as $key => $value) {
            if (!isset($after[$key])) {
                $diff['removed'][$key] = $value;
            }
        }
        
        return $diff;
    }
}

// Generate timeline report
$timelineReport = SnapshotReportGenerator::generateTimelineReport('user-1');
echo "   User #1 Timeline Report:\n";
foreach ($timelineReport as $entry) {
    $data = $entry['data'];
    $status = $data['status'] ?? 'N/A';
    $name = $data['name'] ?? 'N/A';
    echo "     [{$entry['timestamp']}] {$entry['label']}: {$name} ({$status})\n";
}

// 3. Summary report
echo "\n3. Generating summary report:\n";
$summaryReport = SnapshotReportGenerator::generateSummaryReport();

echo "   Overall Statistics:\n";
echo "     - Total snapshots: {$summaryReport['total_snapshots']}\n";
echo "     - Models tracked:\n";
foreach ($summaryReport['by_model'] as $model => $count) {
    echo "       * {$model}: {$count} snapshots\n";
}
echo "     - Activity by date:\n";
foreach ($summaryReport['by_date'] as $date => $count) {
    echo "       * {$date}: {$count} snapshots\n";
}

// 4. Change analysis report
echo "\n4. Generating change analysis report:\n";
$changeReport = SnapshotReportGenerator::generateChangeAnalysisReport();

foreach ($changeReport as $model => $changes) {
    echo "   {$model} Change History:\n";
    foreach ($changes as $change) {
        echo "     Change: {$change['from']} → {$change['to']}\n";
        if (isset($change['changes']['modified'])) {
            foreach ($change['changes']['modified'] as $field => $fieldChange) {
                echo "       - {$field}: '{$fieldChange['from']}' → '{$fieldChange['to']}'\n";
            }
        }
        if (isset($change['changes']['added'])) {
            foreach ($change['changes']['added'] as $field => $value) {
                echo "       + {$field}: '{$value}' (added)\n";
            }
        }
    }
}

// 5. HTML report generation
echo "\n5. Generating HTML report:\n";

class HtmlReportGenerator
{
    public static function generateHtmlReport(array $timelineData, array $summaryData, array $changeData): string
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Laravel Snapshot Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1, h2 { color: #333; }
        .summary { background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .timeline { margin: 20px 0; }
        .timeline-item { margin: 10px 0; padding: 10px; border-left: 4px solid #007cba; }
        .change { background: #fff3cd; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .stats { display: flex; gap: 20px; }
        .stat-box { background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; flex: 1; }
    </style>
</head>
<body>
    <h1>Laravel Snapshot Report</h1>
    <p>Generated on: " . date('Y-m-d H:i:s') . "</p>
    
    <div class="summary">
        <h2>Summary Statistics</h2>
        <div class="stats">
            <div class="stat-box">
                <h3>Total Snapshots</h3>
                <p style="font-size: 2em; margin: 0;">{$summaryData['total_snapshots']}</p>
            </div>
            <div class="stat-box">
                <h3>Models Tracked</h3>
                <p style="font-size: 2em; margin: 0;">" . count($summaryData['by_model']) . "</p>
            </div>
        </div>
    </div>
    
    <h2>Timeline</h2>
    <div class="timeline">
HTML;

        foreach ($timelineData as $entry) {
            $data = $entry['data'];
            $name = $data['name'] ?? 'N/A';
            $status = $data['status'] ?? 'N/A';
            $html .= "<div class='timeline-item'>";
            $html .= "<strong>{$entry['label']}</strong> - {$entry['timestamp']}<br>";
            $html .= "User: {$name} (Status: {$status})";
            $html .= "</div>";
        }

        $html .= "</div><h2>Change Analysis</h2>";

        foreach ($changeData as $model => $changes) {
            $html .= "<h3>{$model}</h3>";
            foreach ($changes as $change) {
                $html .= "<div class='change'>";
                $html .= "<strong>Change:</strong> {$change['from']} → {$change['to']}<br>";
                if (isset($change['changes']['modified'])) {
                    foreach ($change['changes']['modified'] as $field => $fieldChange) {
                        $html .= "<em>{$field}:</em> '{$fieldChange['from']}' → '{$fieldChange['to']}'<br>";
                    }
                }
                $html .= "</div>";
            }
        }

        $html .= "</body></html>";

        return $html;
    }
}

$htmlReport = HtmlReportGenerator::generateHtmlReport($timelineReport, $summaryReport, $changeReport);
echo "   ✓ HTML report generated (" . strlen($htmlReport) . " characters)\n";

// In a real application, you would save this to a file
// file_put_contents(storage_path('reports/snapshot-report-' . date('Y-m-d') . '.html'), $htmlReport);

// 6. JSON report generation
echo "\n6. Generating JSON report:\n";

$jsonReport = [
    'metadata' => [
        'generated_at' => date('Y-m-d H:i:s'),
        'generator' => 'Laravel Snapshot Report Generator',
        'version' => '1.0',
    ],
    'summary' => $summaryReport,
    'timeline' => $timelineReport,
    'changes' => $changeReport,
];

$jsonReportString = json_encode($jsonReport, JSON_PRETTY_PRINT);
echo "   ✓ JSON report generated (" . strlen($jsonReportString) . " characters)\n";

// 7. CSV report generation
echo "\n7. Generating CSV report:\n";

class CsvReportGenerator
{
    public static function generateCsvReport(array $timelineData): string
    {
        $csv = "Label,Timestamp,User ID,Name,Email,Status,Login Count,Last Login\n";
        
        foreach ($timelineData as $entry) {
            $data = $entry['data'];
            $csv .= implode(',', [
                $entry['label'],
                $entry['timestamp'],
                $data['id'] ?? '',
                '"' . ($data['name'] ?? '') . '"',
                '"' . ($data['email'] ?? '') . '"',
                $data['status'] ?? '',
                $data['login_count'] ?? '',
                $data['last_login'] ?? '',
            ]) . "\n";
        }
        
        return $csv;
    }
}

$csvReport = CsvReportGenerator::generateCsvReport($timelineReport);
echo "   ✓ CSV report generated (" . strlen($csvReport) . " characters)\n";

// 8. Performance metrics report
echo "\n8. Generating performance metrics report:\n";

class PerformanceReportGenerator
{
    public static function generatePerformanceReport(): array
    {
        $snapshots = Snapshot::list();
        
        $metrics = [
            'snapshot_count' => count($snapshots),
            'average_size' => 0,
            'size_distribution' => [],
            'storage_usage' => 0,
            'oldest_snapshot' => null,
            'newest_snapshot' => null,
        ];
        
        $sizes = [];
        $timestamps = [];
        
        foreach ($snapshots as $label => $snapshot) {
            // Calculate approximate size
            $size = strlen(json_encode($snapshot));
            $sizes[] = $size;
            $metrics['storage_usage'] += $size;
            
            // Track timestamps
            $timestamp = $snapshot['timestamp'] ?? null;
            if ($timestamp) {
                $timestamps[] = $timestamp;
            }
            
            // Size distribution
            $sizeCategory = $size < 1024 ? 'small' : ($size < 10240 ? 'medium' : 'large');
            $metrics['size_distribution'][$sizeCategory] = ($metrics['size_distribution'][$sizeCategory] ?? 0) + 1;
        }
        
        if (!empty($sizes)) {
            $metrics['average_size'] = array_sum($sizes) / count($sizes);
        }
        
        if (!empty($timestamps)) {
            sort($timestamps);
            $metrics['oldest_snapshot'] = $timestamps[0];
            $metrics['newest_snapshot'] = $timestamps[count($timestamps) - 1];
        }
        
        return $metrics;
    }
}

$performanceMetrics = PerformanceReportGenerator::generatePerformanceReport();
echo "   Performance Metrics:\n";
echo "     - Total snapshots: {$performanceMetrics['snapshot_count']}\n";
echo "     - Average size: " . round($performanceMetrics['average_size']) . " bytes\n";
echo "     - Total storage: " . round($performanceMetrics['storage_usage'] / 1024, 2) . " KB\n";
echo "     - Oldest snapshot: {$performanceMetrics['oldest_snapshot']}\n";
echo "     - Newest snapshot: {$performanceMetrics['newest_snapshot']}\n";
echo "     - Size distribution:\n";
foreach ($performanceMetrics['size_distribution'] as $category => $count) {
    echo "       * {$category}: {$count}\n";
}

// 9. Report automation examples
echo "\n9. Report automation examples:\n";

$automationExample = <<<'PHP'
// Console command example
class GenerateSnapshotReportCommand extends Command
{
    protected $signature = 'snapshot:generate-report 
                            {--format=html : Report format (html, json, csv)}
                            {--output= : Output file path}
                            {--model= : Filter by model class}';
    
    public function handle()
    {
        $format = $this->option('format');
        $output = $this->option('output');
        
        // Generate report based on format
        $report = match($format) {
            'json' => $this->generateJsonReport(),
            'csv' => $this->generateCsvReport(),
            default => $this->generateHtmlReport(),
        };
        
        if ($output) {
            file_put_contents($output, $report);
            $this->info("Report saved to: {$output}");
        } else {
            $this->line($report);
        }
    }
}

PHP;

echo $automationExample;

// 10. Cleanup and summary
echo "\n10. Report generation summary:\n";
echo "   ✓ Timeline reports - Show chronological changes\n";
echo "   ✓ Summary reports - Overview statistics\n";
echo "   ✓ Change analysis - Detailed change tracking\n";
echo "   ✓ HTML reports - Visual web-based reports\n";
echo "   ✓ JSON reports - Machine-readable data\n";
echo "   ✓ CSV reports - Spreadsheet-compatible exports\n";
echo "   ✓ Performance metrics - Storage and usage analysis\n";
echo "   ✓ Automated report generation - Schedulable reports\n";

// Cleanup
$deletedCount = Snapshot::clear();
echo "\n   ✓ Cleaned up {$deletedCount} test snapshots\n";

echo "\n=== Report Generation Benefits Demonstrated ===\n";
echo "✓ Multiple report formats (HTML, JSON, CSV)\n";
echo "✓ Timeline and change analysis\n";
echo "✓ Performance and usage metrics\n";
echo "✓ Automated report generation\n";
echo "✓ Visual and machine-readable outputs\n";
echo "✓ Comprehensive audit documentation\n";
echo "✓ Scheduled reporting capabilities\n";

echo "\nReport generation example completed successfully!\n";