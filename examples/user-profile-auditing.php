<?php

declare(strict_types=1);
/**
 * Example: User Profile Auditing
 * Description: Demonstrates comprehensive user profile change tracking and audit trail
 *
 * Prerequisites:
 * - User model with HasSnapshots trait
 * - Laravel Snapshot package configured
 * - Database with snapshots table
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/user-profile-auditing.php';
 */

use App\Models\User;
use Grazulex\LaravelSnapshot\Snapshot;

echo "=== User Profile Auditing Example ===\n\n";

// Create a test user
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'email_verified_at' => now(),
    'password' => bcrypt('password'),
]);

echo "1. Created user: {$user->name} ({$user->email})\n";

// Create initial snapshot for audit baseline
$user->snapshot('profile-created');
echo "2. Created baseline audit snapshot\n";

// Simulate profile updates over time
echo "\n3. Simulating profile changes...\n";

// First change: Update name
$user->update(['name' => 'John H. Doe']);
$user->snapshot('name-updated');
echo "   - Updated name to: {$user->name}\n";

// Second change: Update email
$user->update(['email' => 'john.henry.doe@example.com']);
$user->snapshot('email-updated');
echo "   - Updated email to: {$user->email}\n";

// Third change: Email verification
$user->update(['email_verified_at' => now()]);
$user->snapshot('email-verified');
echo "   - Email verified\n";

// Generate comprehensive audit report
echo "\n4. Generating audit report...\n";

$auditReport = $user->getHistoryReport('json');
$auditData = json_decode($auditReport, true);

echo "   Total changes tracked: {$auditData['statistics']['total_snapshots']}\n";
echo "   Most changed fields:\n";

foreach ($auditData['statistics']['most_changed_fields'] ?? [] as $field => $count) {
    echo "     - {$field}: {$count} times\n";
}

// Show detailed change timeline
echo "\n5. Detailed change timeline:\n";

$timeline = $user->getSnapshotTimeline();
foreach ($timeline as $entry) {
    echo "   [{$entry['created_at']}] {$entry['event_type']}: {$entry['label']}\n";
}

// Compare states for compliance reporting
echo "\n6. Compliance analysis - comparing initial vs current state:\n";

$initialSnapshot = $user->snapshots()->where('label', 'profile-created')->first();
if ($initialSnapshot) {
    $diff = $user->compareWithSnapshot($initialSnapshot->id);
    
    if (isset($diff['modified'])) {
        echo "   Changes made to user profile:\n";
        foreach ($diff['modified'] as $field => $change) {
            echo "     - {$field}: '{$change['from']}' → '{$change['to']}'\n";
        }
    }
    
    if (empty($diff['modified'])) {
        echo "   No changes detected in user profile\n";
    }
}

// Security audit: Check for suspicious changes
echo "\n7. Security audit check...\n";

$recentChanges = $user->snapshots()
    ->where('created_at', '>=', now()->subHours(24))
    ->get();

if ($recentChanges->count() > 5) {
    echo "   ⚠️  WARNING: {$recentChanges->count()} changes in last 24 hours (potential security concern)\n";
} else {
    echo "   ✅ Normal activity: {$recentChanges->count()} changes in last 24 hours\n";
}

// Generate detailed audit statistics
echo "\n8. Detailed audit statistics:\n";

$stats = Snapshot::stats($user)
    ->counters()
    ->changeFrequency()
    ->eventTypeAnalysis()
    ->get();

echo "   - Total snapshots: {$stats['total_snapshots']}\n";
echo "   - Average changes per day: {$stats['average_changes_per_day']}\n";

if (isset($stats['event_type_percentages'])) {
    echo "   - Change distribution:\n";
    foreach ($stats['event_type_percentages'] as $eventType => $percentage) {
        echo "     * {$eventType}: {$percentage}%\n";
    }
}

// Export audit trail for external compliance systems
echo "\n9. Exporting audit trail...\n";

$csvAuditTrail = $user->getHistoryReport('csv');
// In a real application, you might save this to a file or send to an external system
echo "   CSV audit trail ready for export (" . strlen($csvAuditTrail) . " bytes)\n";

// Demonstrate restoration capability for compliance rollback
echo "\n10. Compliance rollback demonstration:\n";

$rollbackSnapshot = $user->snapshots()->where('label', 'name-updated')->first();
if ($rollbackSnapshot) {
    echo "   Current name: {$user->name}\n";
    echo "   Rolling back to snapshot: {$rollbackSnapshot->label}\n";
    
    // In a real scenario, you'd want to create a snapshot before rolling back
    $user->snapshot('before-rollback');
    
    $success = $user->restoreFromSnapshot($rollbackSnapshot->id);
    if ($success) {
        echo "   ✅ Successfully rolled back to: {$user->name}\n";
        $user->snapshot('after-rollback');
    } else {
        echo "   ❌ Rollback failed\n";
    }
}

// Clean up
echo "\n11. Cleaning up...\n";
$deletedSnapshots = Snapshot::clear(get_class($user));
echo "   Deleted {$deletedSnapshots} audit snapshots\n";

$user->delete();
echo "   Deleted test user\n";

echo "\n=== User Profile Auditing Example Complete ===\n";
echo "\nThis example demonstrates:\n";
echo "- Creating audit snapshots for profile changes\n";
echo "- Generating comprehensive audit reports\n";
echo "- Analyzing change patterns for security\n";
echo "- Compliance reporting and export\n";
echo "- Rollback capabilities for compliance\n";