# Basic Usage

This guide covers all the core features of Laravel Snapshot with practical examples.

## Creating Snapshots

### Manual Snapshots

The most basic way to create snapshots is using the static `Snapshot::save()` method:

```php
use Grazulex\LaravelSnapshot\Snapshot;
use App\Models\User;

$user = User::find(1);

// Create a snapshot with a custom label
$snapshot = Snapshot::save($user, 'user-before-update');

// Create a snapshot with auto-generated label  
$snapshot = Snapshot::save($user);  // Generates: manual-2024-07-19-14-30-15
```

### Using the HasSnapshots Trait

Add the trait to your models for convenient methods:

```php
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;

class Order extends Model
{
    use HasSnapshots;
    
    protected $fillable = ['customer_name', 'total', 'status'];
}
```

Now you can create snapshots directly on the model:

```php
$order = Order::find(1);

// Create a snapshot with custom label
$order->snapshot('order-before-processing');

// Create a snapshot with auto-generated label
$order->snapshot();
```

## Loading Snapshots

### By Label

```php
$snapshot = Snapshot::load('user-before-update');

if ($snapshot) {
    echo "Snapshot contains: ";
    print_r($snapshot);
} else {
    echo "Snapshot not found";
}
```

### Using Model Relations

When using the `HasSnapshots` trait:

```php
$user = User::find(1);

// Get all snapshots for this user
$snapshots = $user->snapshots;

// Get the latest snapshot
$latest = $user->snapshots()->first();

// Get snapshots by event type
$manualSnapshots = $user->snapshots()->forEvent('manual')->get();
```

## Comparing Snapshots

### Basic Comparison

```php
// Create two snapshots
$user = User::find(1);
Snapshot::save($user, 'before');

$user->update(['name' => 'Updated Name', 'email' => 'new@email.com']);
Snapshot::save($user, 'after');

// Compare them
$diff = Snapshot::diff('before', 'after');

/*
Result:
[
    'modified' => [
        'name' => [
            'from' => 'Original Name',
            'to' => 'Updated Name'
        ],
        'email' => [
            'from' => 'old@email.com', 
            'to' => 'new@email.com'
        ]
    ],
    'added' => [],
    'removed' => []
]
*/
```

### Model-Based Comparison

Using the trait, you can compare a model with its previous snapshots:

```php
$user = User::find(1);

// Get the latest snapshot ID
$latestSnapshot = $user->snapshots()->first();

// Compare current state with latest snapshot
$diff = $user->compareWithSnapshot($latestSnapshot->id);
```

## Listing Snapshots

### All Snapshots

```php
$allSnapshots = Snapshot::list();

foreach ($allSnapshots as $label => $snapshot) {
    echo "Label: {$label}, Created: {$snapshot['timestamp']}\n";
}
```

### Model-Specific Snapshots

```php
$user = User::find(1);

// Get timeline for this user
$timeline = $user->getSnapshotTimeline();

// With limit
$recentTimeline = $user->getSnapshotTimeline(10); // Last 10 snapshots
```

## Deleting Snapshots

### Single Snapshot

```php
$deleted = Snapshot::delete('user-before-update');

if ($deleted) {
    echo "Snapshot deleted successfully";
}
```

### Multiple Snapshots

```php
// Clear all snapshots for a specific model class
$count = Snapshot::clear('App\Models\User');
echo "Deleted {$count} snapshots";

// Clear all snapshots
$count = Snapshot::clear();
echo "Deleted {$count} snapshots";
```

## Working with Different Data Types

### Eloquent Models

```php
$user = User::with('posts')->find(1);
Snapshot::save($user, 'user-with-relationships');
```

### Arrays

```php
$data = ['name' => 'John', 'age' => 30, 'city' => 'Paris'];
Snapshot::save($data, 'user-data-array');
```

### Objects

```php
$stdClass = (object) ['property' => 'value'];
Snapshot::save($stdClass, 'object-snapshot');
```

### Primitive Values

```php
$simpleValue = 'Hello World';
Snapshot::save($simpleValue, 'string-snapshot');
```

## Timeline and History

### Getting Model Timeline

```php
$order = Order::find(1);

// Get full timeline
$timeline = $order->getSnapshotTimeline();

// Each entry contains:
foreach ($timeline as $entry) {
    echo "ID: {$entry['id']}\n";
    echo "Label: {$entry['label']}\n";
    echo "Event: {$entry['event_type']}\n";
    echo "Created: {$entry['created_at']}\n";
    echo "Data: " . json_encode($entry['data']) . "\n\n";
}
```

### Timeline with Filters

```php
// Get only manual snapshots
$manualSnapshots = $order->snapshots()
    ->forEvent('manual')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Get snapshots from last week
$recentSnapshots = $order->snapshots()
    ->where('created_at', '>=', now()->subWeek())
    ->get();
```

## Statistics and Analytics

### Basic Statistics

```php
use Grazulex\LaravelSnapshot\Snapshot;

// Get stats for all models
$globalStats = Snapshot::stats()->counters()->get();

// Get stats for a specific model
$user = User::find(1);
$userStats = Snapshot::stats($user)
    ->counters()
    ->changeFrequency()
    ->get();

print_r($userStats);
/*
[
    'total_snapshots' => 15,
    'snapshots_by_event' => [
        'manual' => 5,
        'created' => 1, 
        'updated' => 9
    ],
    'changes_by_day' => [
        '2024-07-19' => 3,
        '2024-07-18' => 2,
        '2024-07-17' => 5
    ]
]
*/
```

### Most Changed Fields

```php
$stats = Snapshot::stats($user)
    ->counters()
    ->mostChangedFields()
    ->changeFrequency()
    ->get();

// Export as JSON
$json = Snapshot::stats($user)->counters()->toJson();
```

## Report Generation

### HTML Reports

```php
$user = User::find(1);

// Generate HTML report
$htmlReport = $user->getHistoryReport('html');

// Save to file
file_put_contents('user_history.html', $htmlReport);
```

### JSON Reports

```php
// Generate JSON report
$jsonReport = $user->getHistoryReport('json');

$reportData = json_decode($jsonReport, true);
```

## Model Restoration

### Restore from Snapshot

```php
$user = User::find(1);

// Get a snapshot ID
$snapshot = $user->snapshots()->first();

// Restore the model to this snapshot state
$restored = $user->restoreFromSnapshot($snapshot->id);

if ($restored) {
    echo "User restored successfully";
}
```

## Practical Example: Order Processing

Here's a complete example showing snapshot usage during order processing:

```php
use App\Models\Order;
use Grazulex\LaravelSnapshot\Snapshot;

class OrderProcessor
{
    public function processOrder(Order $order)
    {
        // Snapshot before processing
        Snapshot::save($order, 'order-before-processing');
        
        try {
            // Apply discounts
            $this->applyDiscounts($order);
            Snapshot::save($order, 'order-after-discounts');
            
            // Calculate taxes
            $this->calculateTaxes($order);
            Snapshot::save($order, 'order-after-taxes');
            
            // Process payment
            $this->processPayment($order);
            Snapshot::save($order, 'order-after-payment');
            
            // Update status
            $order->update(['status' => 'completed']);
            Snapshot::save($order, 'order-completed');
            
            return true;
            
        } catch (Exception $e) {
            // Compare current state with before-processing
            $diff = Snapshot::diff('order-before-processing', 
                                   Snapshot::serializeModel($order));
            
            Log::error('Order processing failed', [
                'order_id' => $order->id,
                'changes_made' => $diff,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    public function generateOrderHistory(Order $order)
    {
        // Get complete timeline
        $timeline = $order->getSnapshotTimeline();
        
        // Generate report
        $report = $order->getHistoryReport('html');
        
        return $report;
    }
}
```

## Testing with Snapshots

### Feature Tests

```php
use Grazulex\LaravelSnapshot\Snapshot;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    public function test_user_update_changes_expected_fields()
    {
        $user = User::factory()->create();
        
        // Snapshot before update
        Snapshot::save($user, 'before-update');
        
        // Perform update
        $user->update([
            'name' => 'Updated Name',
            'email' => 'updated@email.com'
        ]);
        
        // Snapshot after update
        Snapshot::save($user, 'after-update');
        
        // Verify changes
        $diff = Snapshot::diff('before-update', 'after-update');
        
        $this->assertArrayHasKey('modified', $diff);
        $this->assertArrayHasKey('name', $diff['modified']);
        $this->assertArrayHasKey('email', $diff['modified']);
        $this->assertEquals('Updated Name', $diff['modified']['name']['to']);
    }
}
```

## Error Handling

### Handling Missing Snapshots

```php
try {
    $diff = Snapshot::diff('non-existent-1', 'non-existent-2');
} catch (InvalidArgumentException $e) {
    echo "Error: " . $e->getMessage();
    // Error: Snapshot 'non-existent-1' not found
}
```

### Storage Errors

```php
try {
    Snapshot::save($model, 'test-snapshot');
} catch (Exception $e) {
    Log::error('Failed to create snapshot', [
        'model' => get_class($model),
        'error' => $e->getMessage()
    ]);
}
```

## Next Steps

- [Learn about console commands](console-commands.md)
- [Configure automatic snapshots](automatic-snapshots.md)
- [Explore advanced features](advanced-usage.md)
- [See more examples](../examples/README.md)