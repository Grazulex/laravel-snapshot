<?php
/**
 * Example: Basic Snapshot Usage
 * Description: Demonstrates basic snapshot creation, loading, and comparison
 * 
 * Prerequisites:
 * - User model with factory
 * - Laravel Snapshot package configured
 * 
 * Usage:
 * php artisan tinker
 * >>> include 'examples/basic-usage.php';
 */

use App\Models\User;
use Grazulex\LaravelSnapshot\Snapshot;

echo "=== Laravel Snapshot Basic Usage Example ===\n\n";

// Create a test user
$user = User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

echo "1. Created user: {$user->name} ({$user->email})\n";

// Create initial snapshot
$snapshot1 = Snapshot::save($user, 'user-initial');
echo "2. Created initial snapshot with label: user-initial\n";

// Modify the user
$user->update([
    'name' => 'John Smith',
    'email' => 'john.smith@example.com'
]);

echo "3. Updated user: {$user->name} ({$user->email})\n";

// Create second snapshot
$snapshot2 = Snapshot::save($user, 'user-updated');
echo "4. Created updated snapshot with label: user-updated\n";

// Compare snapshots
$diff = Snapshot::diff('user-initial', 'user-updated');
echo "5. Comparison results:\n";

if (isset($diff['modified'])) {
    foreach ($diff['modified'] as $field => $changes) {
        echo "   - {$field}: '{$changes['from']}' → '{$changes['to']}'\n";
    }
}

if (isset($diff['added'])) {
    foreach ($diff['added'] as $field => $value) {
        echo "   + {$field}: '{$value}' (added)\n";
    }
}

if (isset($diff['removed'])) {
    foreach ($diff['removed'] as $field => $value) {
        echo "   - {$field}: '{$value}' (removed)\n";
    }
}

// Load and inspect snapshots
echo "\n6. Loading snapshots:\n";

$loadedSnapshot1 = Snapshot::load('user-initial');
$loadedSnapshot2 = Snapshot::load('user-updated');

echo "   Initial snapshot contains:\n";
echo "   - Name: " . $loadedSnapshot1['attributes']['name'] . "\n";
echo "   - Email: " . $loadedSnapshot1['attributes']['email'] . "\n";

echo "   Updated snapshot contains:\n";
echo "   - Name: " . $loadedSnapshot2['attributes']['name'] . "\n";
echo "   - Email: " . $loadedSnapshot2['attributes']['email'] . "\n";

// List all snapshots
echo "\n7. All available snapshots:\n";
$allSnapshots = Snapshot::list();

foreach ($allSnapshots as $label => $snapshot) {
    echo "   - {$label}: " . ($snapshot['timestamp'] ?? 'N/A') . "\n";
}

// Create snapshot with auto-generated label
$autoSnapshot = Snapshot::save($user);
echo "\n8. Created auto-labeled snapshot\n";

// Demonstrate with different data types
echo "\n9. Snapshot different data types:\n";

// Array snapshot
$arrayData = ['name' => 'Test Array', 'values' => [1, 2, 3], 'nested' => ['key' => 'value']];
Snapshot::save($arrayData, 'array-example');
echo "   - Created array snapshot\n";

// Object snapshot
$objectData = (object) ['property' => 'test', 'number' => 42];
Snapshot::save($objectData, 'object-example');
echo "   - Created object snapshot\n";

// Primitive snapshot
Snapshot::save('Simple string value', 'string-example');
echo "   - Created string snapshot\n";

echo "\n10. Final snapshot list:\n";
$finalList = Snapshot::list();
foreach ($finalList as $label => $snapshot) {
    $dataType = $snapshot['class'] ?? 'unknown';
    echo "   - {$label}: {$dataType}\n";
}

// Cleanup
echo "\n11. Cleaning up test snapshots...\n";
$deletedCount = Snapshot::clear();
echo "    Deleted {$deletedCount} snapshots\n";

// Clean up test user
$user->delete();
echo "    Deleted test user\n";

echo "\nExample completed successfully!\n";