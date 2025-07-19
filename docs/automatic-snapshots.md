# Automatic Snapshots

Automatic snapshots are created automatically when model events occur (created, updated, deleted). This provides seamless audit trails without manual intervention.

## Overview

When enabled, automatic snapshots capture model state changes triggered by Eloquent model events. This is perfect for:

- **Audit trails** - Track all changes to critical models
- **Compliance** - Meet regulatory requirements automatically  
- **Debugging** - See exactly what changed and when
- **Analytics** - Analyze change patterns over time

## Configuration

### Enable Automatic Snapshots

```php
// config/snapshot.php
'automatic' => [
    'enabled' => true,                              // Master switch
    'events' => ['created', 'updated', 'deleted'],  // Default events to capture
    'exclude_fields' => [                           // Fields to exclude from snapshots
        'updated_at', 
        'created_at', 
        'password', 
        'remember_token'
    ],
    'models' => [
        // Model-specific configuration
        'App\Models\User' => ['created', 'updated'],      
        'App\Models\Order' => ['created', 'updated', 'deleted'],
        'App\Models\Payment' => ['created', 'updated'],
        'App\Models\Product' => ['updated'], // Only track updates (price changes, etc.)
    ],
],
```

### Model Setup

Add the `HasSnapshots` trait to models you want to track:

```php
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasSnapshots;
    
    protected $fillable = ['name', 'email', 'role'];
}

class Order extends Model  
{
    use HasSnapshots;
    
    protected $fillable = ['customer_name', 'total', 'status'];
}
```

The trait automatically sets up event listeners based on your configuration.

## Event Types

### created

Triggered when a new model instance is created.

```php
// This will create an automatic snapshot
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Snapshot created with label like:
// "auto-User-1-created-2024-07-19-10-30-15"
```

### updated

Triggered when an existing model is updated.

```php
$user = User::find(1);
$user->update(['name' => 'John Smith']);
// Snapshot created automatically
```

### deleted

Triggered when a model is deleted (soft or hard delete).

```php
$user = User::find(1);
$user->delete();
// Snapshot created before deletion
```

## Per-Model Configuration

You can configure different events for different models:

### E-commerce Example

```php
'models' => [
    // Track all user changes except deletion
    'App\Models\User' => ['created', 'updated'],
    
    // Track complete order lifecycle
    'App\Models\Order' => ['created', 'updated', 'deleted'],
    
    // Only track payment creation and updates (not deletion for audit)
    'App\Models\Payment' => ['created', 'updated'],
    
    // Only track product updates (price changes, inventory, etc.)
    'App\Models\Product' => ['updated'],
    
    // Track everything for audit-critical models
    'App\Models\Transaction' => ['created', 'updated', 'deleted'],
    
    // Only track invoice updates (status changes, payments, etc.)
    'App\Models\Invoice' => ['updated'],
],
```

### Content Management Example

```php
'models' => [
    // Track article creation and updates
    'App\Models\Article' => ['created', 'updated'],
    
    // Track page modifications  
    'App\Models\Page' => ['updated'],
    
    // Track comment lifecycle
    'App\Models\Comment' => ['created', 'updated', 'deleted'],
    
    // Only track user profile updates
    'App\Models\User' => ['updated'],
],
```

## Field Exclusion

### Global Exclusions

Fields excluded from all automatic snapshots:

```php
'automatic' => [
    'exclude_fields' => [
        'updated_at',           // Usually not needed in snapshots
        'created_at',           // Usually not needed in snapshots
        'password',             // Security - never snapshot passwords
        'remember_token',       // Security token
        'api_token',            // Security token
        'email_verified_at',    // Usually not critical for business logic
        'deleted_at',           // Soft delete timestamp
    ],
],
```

### Model-Specific Exclusions

You can exclude fields for specific models by configuring in the model:

```php
class User extends Model
{
    use HasSnapshots;
    
    protected $snapshotExclude = [
        'last_login_at',
        'login_count', 
        'session_data',
    ];
}
```

### Sensitive Data Exclusion

Always exclude sensitive or PII data:

```php
'exclude_fields' => [
    // Authentication
    'password',
    'remember_token',
    'api_token',
    'two_factor_secret',
    
    // Personal Information (depending on requirements)
    'ssn',
    'credit_card_number', 
    'bank_account',
    
    // Internal System Fields
    'created_at',
    'updated_at', 
    'deleted_at',
    
    // Large/Binary Fields
    'profile_picture',
    'document_content',
    'binary_data',
],
```

## Snapshot Labels

Automatic snapshots use generated labels with this format:

```
auto-{ModelName}-{ID}-{EventType}-{Timestamp}
```

Examples:
- `auto-User-1-created-2024-07-19-10-30-15`
- `auto-Order-123-updated-2024-07-19-14-25-30`
- `auto-Payment-456-deleted-2024-07-19-16-45-00`

You can customize the label format:

```php
// In your model
class Order extends Model
{
    use HasSnapshots;
    
    protected function generateAutoSnapshotLabel(string $eventType): string
    {
        return "order-{$this->order_number}-{$eventType}-" . now()->format('Y-m-d-H-i-s');
    }
}
```

## Performance Considerations

### High-Volume Applications

For applications with frequent model changes:

```php
'automatic' => [
    'enabled' => true,
    
    // Limit to most critical models only
    'models' => [
        'App\Models\Transaction' => ['created'],  // Only creation
        'App\Models\Order' => ['created', 'updated'],
    ],
    
    // Exclude more fields
    'exclude_fields' => [
        'updated_at', 'created_at', 'last_activity',
        'view_count', 'click_count', // Frequently changing fields
    ],
],
```

### Batch Operations

Automatic snapshots work with batch operations:

```php
// Each user creation will create a snapshot
User::factory()->count(100)->create();

// Each update will create a snapshot
User::where('role', 'guest')->update(['role' => 'user']);
```

To avoid this in specific cases:

```php
// Temporarily disable automatic snapshots
config(['snapshot.automatic.enabled' => false]);

// Perform batch operations
User::where('role', 'guest')->update(['role' => 'user']);

// Re-enable
config(['snapshot.automatic.enabled' => true]);
```

## Working with Automatic Snapshots

### Querying Automatic Snapshots

```php
use Grazulex\LaravelSnapshot\Models\ModelSnapshot;

// Get all automatic snapshots for a user
$autoSnapshots = ModelSnapshot::where('model_type', User::class)
    ->where('model_id', 1)
    ->where('event_type', '!=', 'manual')
    ->orderBy('created_at', 'desc')
    ->get();

// Get snapshots by event type
$createdSnapshots = ModelSnapshot::forEvent('created')->get();
$updatedSnapshots = ModelSnapshot::forEvent('updated')->get();
$deletedSnapshots = ModelSnapshot::forEvent('deleted')->get();
```

### Using Model Methods

```php
$user = User::find(1);

// Get all snapshots (including automatic)
$allSnapshots = $user->snapshots;

// Get only automatic snapshots
$autoSnapshots = $user->snapshots()->where('event_type', '!=', 'manual')->get();

// Get timeline with automatic snapshots
$timeline = $user->getSnapshotTimeline();
```

## Conditional Snapshots

You can add conditions to prevent snapshots in certain scenarios:

```php
class User extends Model
{
    use HasSnapshots;
    
    /**
     * Determine if automatic snapshot should be created
     */
    protected function shouldCreateAutomaticSnapshot(string $eventType): bool
    {
        // Don't snapshot if only timestamps changed
        if ($eventType === 'updated' && $this->isDirty(['updated_at'])) {
            return false;
        }
        
        // Don't snapshot system users
        if ($this->email === 'system@example.com') {
            return false;
        }
        
        // Don't snapshot during seeding
        if (app()->runningInConsole() && app()->environment('testing')) {
            return false;
        }
        
        return true;
    }
}
```

## Real-World Examples

### E-commerce Order Tracking

```php
class Order extends Model
{
    use HasSnapshots;
    
    protected $fillable = [
        'order_number', 'customer_id', 'status', 
        'subtotal', 'tax', 'total', 'notes'
    ];
}
```

Configuration:
```php
'models' => [
    'App\Models\Order' => ['created', 'updated'], // Track creation and all updates
],
```

This automatically captures:
- Order creation
- Status changes (pending → processing → shipped → delivered)
- Price adjustments
- Any other order modifications

### User Audit Trail

```php
class User extends Model
{
    use HasSnapshots;
    
    protected $fillable = [
        'name', 'email', 'role', 'department', 'active'
    ];
}
```

Configuration:
```php
'models' => [
    'App\Models\User' => ['created', 'updated'], // Track user changes
],
'exclude_fields' => [
    'password', 'remember_token', 'email_verified_at',
    'last_login_at', 'created_at', 'updated_at'
],
```

This captures:
- New user registrations
- Profile updates
- Role/permission changes
- Account status changes

### Financial Transaction Auditing

```php
class Transaction extends Model
{
    use HasSnapshots;
    
    protected $fillable = [
        'account_id', 'amount', 'type', 'status', 
        'description', 'reference_number'
    ];
}
```

Configuration:
```php
'models' => [
    'App\Models\Transaction' => ['created', 'updated'], // Complete audit trail
],
'exclude_fields' => [
    'created_at', 'updated_at' // Keep business data only
],
```

## Testing with Automatic Snapshots

### Disable in Tests

```php
// In your TestCase
protected function setUp(): void
{
    parent::setUp();
    
    // Disable automatic snapshots for faster tests
    config(['snapshot.automatic.enabled' => false]);
    
    // Or use array storage
    Snapshot::setStorage(new ArrayStorage());
}
```

### Test Automatic Behavior

```php
public function test_user_creation_creates_snapshot()
{
    // Enable automatic snapshots
    config(['snapshot.automatic.enabled' => true]);
    config(['snapshot.automatic.models.App\Models\User' => ['created']]);
    
    // Create user - should trigger automatic snapshot
    $user = User::factory()->create();
    
    // Verify snapshot was created
    $snapshots = ModelSnapshot::where('model_type', User::class)
        ->where('model_id', $user->id)
        ->where('event_type', 'created')
        ->get();
        
    $this->assertCount(1, $snapshots);
}
```

## Monitoring and Analytics

### Snapshot Statistics

```php
// Get statistics for automatic snapshots
$stats = Snapshot::stats()
    ->counters()
    ->changeFrequency()
    ->get();

// Results include counts by event type:
// ['snapshots_by_event' => ['created' => 50, 'updated' => 200, 'deleted' => 10]]
```

### Change Pattern Analysis

```php
// Analyze which models change most frequently
$modelStats = ModelSnapshot::selectRaw('model_type, COUNT(*) as count')
    ->where('event_type', '!=', 'manual')
    ->groupBy('model_type')
    ->orderBy('count', 'desc')
    ->get();

// Analyze change frequency by day
$dailyChanges = ModelSnapshot::selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->where('event_type', '!=', 'manual')
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->get();
```

## Best Practices

1. **Start Small**: Enable for critical models first
2. **Monitor Performance**: Watch for impact on high-frequency operations
3. **Exclude Unnecessary Fields**: Don't snapshot timestamps or computed fields
4. **Set Retention Policies**: Prevent unlimited growth
5. **Use Appropriate Events**: Not all models need all events
6. **Test Configuration**: Verify in development before production
7. **Monitor Storage Growth**: Especially with high-volume models

## Next Steps

- [Scheduled Snapshots](scheduled-snapshots.md) - Time-based automatic snapshots
- [Reports & Analytics](reports-analytics.md) - Analyze automatic snapshot data
- [Performance Optimization](advanced-usage.md#performance) - Handle high-volume scenarios