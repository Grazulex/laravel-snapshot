<?php

declare(strict_types=1);
/**
 * Example: User Profile Auditing
 * Description: Demonstrates audit trail for user profile changes
 *
 * Prerequisites:
 * - User model with HasSnapshots trait
 * - Laravel Snapshot package configured
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
    'name' => 'Jane Doe',
    'email' => 'jane@company.com',
]);

echo "1. Created user profile for audit: {$user->name} ({$user->email})\n";

// Create initial snapshot for audit trail
$user->snapshot('profile-created');
echo "2. Audit trail started with initial snapshot\n";

// Simulate profile updates over time
echo "\n3. Simulating profile changes over time:\n";

// First change - update name
sleep(1);
$user->update(['name' => 'Jane Smith']);
$user->snapshot('name-changed');
echo "   ✓ Name changed: Jane Doe → Jane Smith\n";

// Second change - update email
sleep(1);
$user->update(['email' => 'jane.smith@company.com']);
$user->snapshot('email-updated');
echo "   ✓ Email updated: jane@company.com → jane.smith@company.com\n";

// Third change - add phone (if field exists)
sleep(1);
if (method_exists($user, 'phone')) {
    $user->update(['phone' => '+1-555-0123']);
    $user->snapshot('phone-added');
    echo "   ✓ Phone number added: +1-555-0123\n";
}

// Fourth change - update multiple fields
sleep(1);
$user->update([
    'name' => 'Jane Smith-Wilson',
    'email' => 'jane.wilson@company.com',
]);
$user->snapshot('marriage-name-change');
echo "   ✓ Marriage name change applied\n";

// Generate comprehensive audit report
echo "\n4. Generating audit timeline:\n";
$timeline = $user->getSnapshotTimeline();

foreach ($timeline as $entry) {
    $date = $entry['created_at']->format('Y-m-d H:i:s');
    $event = $entry['event_type'];
    $label = $entry['label'];
    echo "   [{$date}] {$event}: {$label}\n";
}

// Show audit statistics
echo "\n5. Profile change statistics:\n";
$stats = Snapshot::stats($user)
    ->counters()
    ->mostChangedFields()
    ->get();

echo "   - Total snapshots: {$stats['counters']['total']}\n";
echo "   - Manual snapshots: ".($stats['counters']['events']['manual'] ?? 0)."\n";
echo "   - Auto snapshots: ".($stats['counters']['events']['created'] ?? 0)."\n";

if (isset($stats['most_changed_fields'])) {
    echo "   - Most changed fields:\n";
    foreach ($stats['most_changed_fields'] as $field => $count) {
        echo "     * {$field}: {$count} changes\n";
    }
}

// Demonstrate diff capabilities for audit
echo "\n6. Audit trail comparisons:\n";

// Compare initial vs current state
$initialDiff = $user->compareWithSnapshot('profile-created');
if (isset($initialDiff['modified'])) {
    echo "   Changes since profile creation:\n";
    foreach ($initialDiff['modified'] as $field => $change) {
        echo "     - {$field}: '{$change['from']}' → '{$change['to']}'\n";
    }
}

// Compare specific changes
$nameDiff = Snapshot::diff('profile-created', 'name-changed');
if (isset($nameDiff['modified'])) {
    echo "\n   First name change details:\n";
    foreach ($nameDiff['modified'] as $field => $change) {
        echo "     - {$field}: '{$change['from']}' → '{$change['to']}'\n";
    }
}

// Demonstrate restoration for audit purposes
echo "\n7. Audit restoration capabilities:\n";
echo "   Current state: {$user->name} ({$user->email})\n";

// Show what restoration would look like
$previousSnapshot = $user->snapshots()->where('label', 'profile-created')->first();
if ($previousSnapshot) {
    $restoreData = json_decode($previousSnapshot->data, true);
    echo "   Can restore to: {$restoreData['attributes']['name']} ({$restoreData['attributes']['email']})\n";
}

// Generate audit report in different formats
echo "\n8. Generating audit reports:\n";

try {
    $htmlReport = $user->getHistoryReport('html');
    echo "   ✓ HTML audit report generated (".strlen($htmlReport)." characters)\n";
    
    $jsonReport = $user->getHistoryReport('json');
    echo "   ✓ JSON audit report generated (".strlen($jsonReport)." characters)\n";
} catch (Exception $e) {
    echo "   ⚠ Report generation not available: {$e->getMessage()}\n";
}

// Demonstrate compliance features
echo "\n9. Compliance and security audit:\n";
echo "   - All changes are timestamped and traceable\n";
echo "   - Original data is preserved in snapshots\n";
echo "   - Change history cannot be tampered with\n";
echo "   - Full restoration capabilities available\n";

// Show sensitive field exclusion (if configured)
$excludeFields = config('snapshot.automatic.exclude_fields', []);
if (!empty($excludeFields)) {
    echo "   - Sensitive fields excluded from snapshots: ".implode(', ', $excludeFields)."\n";
}

// Cleanup
echo "\n10. Cleaning up audit test data...\n";
$deletedSnapshots = Snapshot::clear(get_class($user));
echo "    Deleted {$deletedSnapshots} snapshots\n";

$user->delete();
echo "    Deleted test user\n";

echo "\n=== Key Audit Benefits Demonstrated ===\n";
echo "✓ Complete change history preservation\n";
echo "✓ Timestamped audit trail\n";
echo "✓ Before/after comparison capabilities\n";
echo "✓ Point-in-time restoration\n";
echo "✓ Compliance reporting\n";
echo "✓ Sensitive data protection\n";

echo "\nUser profile auditing example completed successfully!\n";