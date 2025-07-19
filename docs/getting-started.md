# Getting Started

This guide will walk you through the basic setup and first steps with Laravel Snapshot.

## What is Laravel Snapshot?

Laravel Snapshot is a powerful package that allows you to capture and store the state of any Eloquent model at a specific point in time. This is perfect for:

- **Audit trails** - Track changes to critical business data
- **Debugging** - Compare model states before and after operations
- **Testing** - Verify model changes during feature tests
- **Backup** - Create snapshots before risky operations
- **Analytics** - Analyze how your data changes over time

## Key Concepts

### Snapshots
A snapshot is a point-in-time capture of a model's state, including all its attributes and optionally its relationships.

### Labels
Every snapshot has a unique label that identifies it. You can provide your own meaningful labels or let the package generate them automatically.

### Storage Backends
Snapshots can be stored in multiple ways:
- **Database** (default) - Stored in a `snapshots` table
- **File system** - Each snapshot as a JSON file
- **In-memory** - For testing purposes

### Event Types
Snapshots can be triggered by:
- **Manual** - Explicitly created by calling `Snapshot::save()`
- **Automatic** - Triggered by model events (created, updated, deleted)
- **Scheduled** - Created periodically via cron jobs

## Your First Snapshot

Let's create your first snapshot:

```php
use App\Models\User;
use Grazulex\LaravelSnapshot\Snapshot;

// Get a user
$user = User::find(1);

// Create a snapshot
Snapshot::save($user, 'user-before-update');

// Make some changes
$user->update(['name' => 'John Updated', 'email' => 'john.updated@example.com']);

// Create another snapshot
Snapshot::save($user, 'user-after-update');

// Compare the two snapshots
$diff = Snapshot::diff('user-before-update', 'user-after-update');

dd($diff);
```

## Using the HasSnapshots Trait

For models that you want to snapshot frequently, add the `HasSnapshots` trait:

```php
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasSnapshots;
    
    protected $fillable = ['customer_name', 'total', 'status'];
}
```

Now you can use convenient methods:

```php
$order = Order::create([
    'customer_name' => 'Jane Doe',
    'total' => 99.99,
    'status' => 'pending'
]);

// Create a snapshot
$order->snapshot('order-created');

// Get all snapshots for this order
$snapshots = $order->snapshots;

// Get timeline
$timeline = $order->getSnapshotTimeline();

// Generate history report
$report = $order->getHistoryReport('html');
```

## Console Commands

Laravel Snapshot includes several Artisan commands:

```bash
# Create a snapshot
php artisan snapshot:save "App\Models\User" --id=1 --label=before-update

# List all snapshots
php artisan snapshot:list

# Compare two snapshots
php artisan snapshot:diff before-update after-update

# Generate a report
php artisan snapshot:report --model="App\Models\User" --id=1
```

## Next Steps

Now that you understand the basics, explore these topics:

1. [Installation](installation.md) - Detailed installation instructions
2. [Configuration](configuration.md) - Configure automatic snapshots and storage
3. [Basic Usage](basic-usage.md) - Learn all the core features
4. [Examples](../examples/README.md) - See practical examples