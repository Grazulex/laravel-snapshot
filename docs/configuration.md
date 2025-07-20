# Configuration

Laravel Snapshot provides extensive configuration options through the `config/snapshot.php` file. This guide covers all available settings and their usage.

## Publishing Configuration

First, publish the configuration file:

```bash
php artisan vendor:publish --tag=snapshot-config
```

This creates `config/snapshot.php` with all available options.

## Storage Configuration

### Default Storage Driver

```php
'default' => 'database', // Options: 'database', 'file', 'array'
```

### Storage Drivers

```php
'drivers' => [
    'database' => [
        'driver' => 'database',
        'table' => 'snapshots',
    ],

    'file' => [
        'driver' => 'file',
        'path' => storage_path('snapshots'),
    ],

    'array' => [
        'driver' => 'array', // In-memory, for testing
    ],
],
```

#### Database Storage
- **Pros**: Fast queries, relationships, built-in indexing
- **Cons**: Larger database size
- **Best for**: Production environments with frequent querying

#### File Storage  
- **Pros**: Easy backup, human-readable files, no database impact
- **Cons**: Slower for large datasets, no built-in indexing
- **Best for**: Archival storage, smaller applications

#### Array Storage
- **Pros**: Fast, no persistence overhead
- **Cons**: Lost on application restart
- **Best for**: Testing only

## Serialization Options

Control how models are serialized:

```php
'serialization' => [
    'include_hidden' => false,        // Include hidden model attributes
    'include_timestamps' => true,     // Include created_at/updated_at
    'include_relationships' => true,  // Include loaded relationships
    'max_relationship_depth' => 3,    // Maximum depth for nested relationships
],
```

### Examples:

```php
// Include hidden fields (e.g., password hashes)
'include_hidden' => true,

// Exclude timestamps to focus on business data
'include_timestamps' => false,

// Disable relationships for smaller snapshots
'include_relationships' => false,
```

## Retention Configuration

Manage snapshot lifecycle:

```php
'retention' => [
    'enabled' => true,        // Enable automatic cleanup
    'days' => 30,            // Keep snapshots for 30 days
    'auto_cleanup' => true,   // Run cleanup automatically
],
```

Setup scheduled cleanup in your console kernel:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('snapshot:clear', ['--older-than=30', '--confirm'])->daily();
}
```

## Automatic Snapshots

Configure automatic snapshot creation:

```php
'automatic' => [
    'enabled' => false,                              // Master switch
    'events' => ['created', 'updated', 'deleted'],   // Default events
    'exclude_fields' => [                            // Fields to exclude
        'updated_at', 
        'created_at', 
        'password', 
        'remember_token'
    ],
    'models' => [
        'App\Models\User' => ['created', 'updated'],      // User-specific events
        'App\Models\Order' => ['created', 'updated', 'deleted'], // Order tracking
        // 'App\Models\Invoice' => ['updated'],           // Invoice changes only
    ],
],
```

### Per-Model Configuration

You can configure different events for different models:

```php
'models' => [
    // Track all changes to orders
    'App\Models\Order' => ['created', 'updated', 'deleted'],
    
    // Only track user creation and updates (not deletion)
    'App\Models\User' => ['created', 'updated'],
    
    // Only track invoice updates (when signed, paid, etc.)
    'App\Models\Invoice' => ['updated'],
    
    // Track everything for audit-critical models
    'App\Models\Transaction' => ['created', 'updated', 'deleted'],
],
```

## Scheduled Snapshots

Configure periodic snapshots via cron:

```php
'scheduled' => [
    'enabled' => false,              // Enable scheduled snapshots
    'default_frequency' => 'daily',  // Default frequency
    'models' => [
        'App\Models\User' => 'daily',     // Daily user snapshots
        'App\Models\Order' => 'hourly',   // Hourly order snapshots  
        'App\Models\Invoice' => 'weekly', // Weekly invoice snapshots
    ],
],
```

Add to your console kernel:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    if (config('snapshot.scheduled.enabled')) {
        // Run scheduled snapshots for different models
        $schedule->command('snapshot:schedule', ['App\Models\User', '--limit=1000'])->dailyAt('02:00');
        $schedule->command('snapshot:schedule', ['App\Models\Order', '--limit=500'])->hourly();
    }
}
```

## Reports & Analytics

Configure reporting features:

```php
'reports' => [
    'enabled' => true,                           // Enable reports
    'formats' => ['html', 'json', 'csv'],       // Available formats
    'template' => 'default',                     // HTML template
    'max_timeline_entries' => 100,              // Limit timeline entries
    'include_diffs' => true,                     // Include diff analysis
],
```

## Environment-Specific Configuration

### Development Environment

```php
// config/snapshot.php or .env.local
'default' => 'array',  // Fast, no persistence
'automatic' => ['enabled' => true],  // Track all changes
'retention' => ['days' => 7],  // Shorter retention
```

### Testing Environment

```php
// In your tests
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Snapshot;

public function setUp(): void
{
    parent::setUp();
    
    // Use memory storage for tests
    Snapshot::setStorage(new ArrayStorage());
}
```

### Production Environment

```php
'default' => 'database',
'automatic' => [
    'enabled' => true,
    'models' => [
        // Only track critical models
        'App\Models\Order' => ['created', 'updated'],
        'App\Models\Payment' => ['created', 'updated'],
    ],
],
'retention' => [
    'enabled' => true,
    'days' => 90,  // Longer retention
    'auto_cleanup' => true,
],
```

## Performance Tuning

### Large Models

For models with many attributes or relationships:

```php
'serialization' => [
    'include_relationships' => false,  // Skip relationships
    'max_relationship_depth' => 1,     // Limit depth
],

'automatic' => [
    'exclude_fields' => [
        'large_text_field',
        'binary_data',
        'computed_field',
    ],
],
```

### High-Volume Applications

```php
// Use file storage to reduce database load
'default' => 'file',

// Shorter retention
'retention' => ['days' => 14],

// Limit automatic snapshots
'automatic' => [
    'models' => [
        // Only most critical models
        'App\Models\Transaction' => ['created'],
    ],
],
```

## Security Considerations

### Sensitive Data

Always exclude sensitive fields:

```php
'automatic' => [
    'exclude_fields' => [
        'password',
        'remember_token', 
        'api_token',
        'credit_card_number',
        'social_security_number',
        'bank_account',
    ],
],
```

### Access Control

Implement access control in your application:

```php
// In your controller/middleware
public function viewSnapshots(Request $request)
{
    if (!$request->user()->can('view-snapshots')) {
        abort(403);
    }
    
    // Show snapshots...
}
```

## Example: Complete E-commerce Configuration

```php
<?php
return [
    'default' => 'database',
    
    'retention' => [
        'enabled' => true,
        'days' => 60,
        'auto_cleanup' => true,
    ],
    
    'automatic' => [
        'enabled' => true,
        'exclude_fields' => [
            'updated_at', 'created_at', 'password', 'remember_token',
            'stripe_id', 'card_brand', 'card_last_four',
        ],
        'models' => [
            'App\Models\Order' => ['created', 'updated'],
            'App\Models\Payment' => ['created', 'updated'],
            'App\Models\Product' => ['updated'], // Track price changes
            'App\Models\User' => ['updated'],    // Track profile changes
        ],
    ],
    
    'scheduled' => [
        'enabled' => true,
        'models' => [
            'App\Models\Inventory' => 'daily',  // Daily inventory snapshots
        ],
    ],
    
    'reports' => [
        'enabled' => true,
        'formats' => ['html', 'json'],
        'max_timeline_entries' => 50,
        'include_diffs' => true,
    ],
];
```

## Next Steps

- [Learn basic usage](basic-usage.md)
- [Explore console commands](console-commands.md)
- [See practical examples](../examples/README.md)