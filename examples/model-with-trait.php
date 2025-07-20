<?php

declare(strict_types=1);
/**
 * Example: Model with HasSnapshots Trait
 * Description: Demonstrates using the HasSnapshots trait for convenient model snapshot operations
 *
 * Prerequisites:
 * - Order model with HasSnapshots trait
 * - Order factory or ability to create orders
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/model-with-trait.php';
 */

use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

echo "=== Laravel Snapshot HasSnapshots Trait Example ===\n\n";

// Define a sample Order model for this example
// In real usage, this would be in your app/Models directory
class ExampleOrder extends Model
{
    use HasSnapshots;

    // Disable timestamps for this example
    public $timestamps = false;

    protected $fillable = ['customer_name', 'total', 'status', 'items_count'];

    protected $table = 'orders'; // Assumes you have an orders table
}

try {
    // Create a test order
    $order = new ExampleOrder();
    $order->fill([
        'customer_name' => 'Jane Doe',
        'total' => 99.99,
        'status' => 'pending',
        'items_count' => 3,
    ]);

    // For this example, we'll simulate saving without actually touching the database
    $order->exists = true;
    $order->id = 1;

    echo "1. Created order for: {$order->customer_name}\n";
    echo "   Total: \${$order->total}, Status: {$order->status}, Items: {$order->items_count}\n";

    // Create initial snapshot using trait method
    $order->snapshot('order-created');
    echo "\n2. Created snapshot 'order-created' using trait method\n";

    // Simulate order processing steps
    $order->status = 'processing';
    $order->snapshot('order-processing');
    echo "3. Updated status to 'processing' and created snapshot\n";

    // Apply discount
    $order->total = 79.99;
    $order->snapshot('order-discounted');
    echo "4. Applied discount (total: \${$order->total}) and created snapshot\n";

    // Complete order
    $order->status = 'completed';
    $order->snapshot('order-completed');
    echo "5. Completed order and created final snapshot\n";

    // Get timeline using trait method
    echo "\n6. Order timeline (using trait method):\n";

    // Note: In real usage, this would query the database
    // For this example, we'll simulate the timeline
    $simulatedTimeline = [
        [
            'id' => 1,
            'label' => 'order-created',
            'event_type' => 'manual',
            'created_at' => now()->subMinutes(10),
            'data' => json_encode(['attributes' => ['status' => 'pending', 'total' => 99.99]]),
        ],
        [
            'id' => 2,
            'label' => 'order-processing',
            'event_type' => 'manual',
            'created_at' => now()->subMinutes(8),
            'data' => json_encode(['attributes' => ['status' => 'processing', 'total' => 99.99]]),
        ],
        [
            'id' => 3,
            'label' => 'order-discounted',
            'event_type' => 'manual',
            'created_at' => now()->subMinutes(5),
            'data' => json_encode(['attributes' => ['status' => 'processing', 'total' => 79.99]]),
        ],
        [
            'id' => 4,
            'label' => 'order-completed',
            'event_type' => 'manual',
            'created_at' => now(),
            'data' => json_encode(['attributes' => ['status' => 'completed', 'total' => 79.99]]),
        ],
    ];

    foreach ($simulatedTimeline as $entry) {
        $data = json_decode($entry['data'], true);
        $status = $data['attributes']['status'];
        $total = $data['attributes']['total'];
        echo "   - {$entry['label']}: Status={$status}, Total=\${$total} ({$entry['created_at']})\n";
    }

    // Compare snapshots using static method
    echo "\n7. Comparing initial vs final state:\n";

    $diff = Snapshot::diff('order-created', 'order-completed');

    if (isset($diff['modified'])) {
        foreach ($diff['modified'] as $field => $changes) {
            echo "   - {$field}: {$changes['from']} → {$changes['to']}\n";
        }
    }

    // Demonstrate auto-generated snapshots
    echo "\n8. Creating snapshot with auto-generated label:\n";
    $autoLabel = $order->snapshot(); // No label provided
    echo "   - Created snapshot with auto-generated label\n";

    // Simulate getting latest snapshot
    echo "\n9. Latest snapshot info:\n";
    $latestSnapshot = Snapshot::load('order-completed');
    if ($latestSnapshot) {
        echo "   - Label: order-completed\n";
        echo "   - Event Type: {$latestSnapshot['event_type']}\n";
        echo "   - Status: {$latestSnapshot['attributes']['status']}\n";
        echo "   - Total: \${$latestSnapshot['attributes']['total']}\n";
    }

    // Demonstrate statistics
    echo "\n10. Snapshot statistics:\n";
    $stats = Snapshot::stats($order)->counters()->get();
    if (isset($stats['total_snapshots'])) {
        echo "   - Total snapshots: {$stats['total_snapshots']}\n";
    }
    if (isset($stats['snapshots_by_event'])) {
        foreach ($stats['snapshots_by_event'] as $eventType => $count) {
            echo "   - {$eventType}: {$count}\n";
        }
    }

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo "Note: This example simulates database operations for demonstration.\n";
    echo "In a real application, ensure your models and database are properly configured.\n";
}

// Cleanup demonstration
echo "\n11. Cleanup:\n";
try {
    $deletedCount = Snapshot::clear();
    echo "   - Deleted {$deletedCount} snapshots\n";
} catch (Exception $e) {
    echo "   - Cleanup simulation (in real app: would delete snapshots)\n";
}

echo "\nExample completed successfully!\n";
echo "\nKey Benefits of HasSnapshots Trait:\n";
echo "- Convenient snapshot() method on models\n";
echo "- Direct access to model's snapshots via relationship\n";
echo "- Timeline and history methods\n";
echo "- Integration with model events for automatic snapshots\n";
