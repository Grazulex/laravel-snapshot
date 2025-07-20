# Laravel Snapshot

<img src="new_logo.png" alt="Laravel Snapshot" width="200">

Advanced model versioning and snapshot system for Laravel applications. Track model changes, create point-in-time snapshots, and restore previous states with comprehensive diff analysis.

[![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-snapshot.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-snapshot)
[![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-snapshot.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-snapshot)
[![License](https://img.shields.io/github/license/grazulex/laravel-snapshot.svg?style=flat-square)](https://github.com/Grazulex/laravel-snapshot/blob/main/LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/grazulex/laravel-snapshot.svg?style=flat-square)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-12.x-ff2d20?style=flat-square&logo=laravel)](https://laravel.com/)
[![Tests](https://img.shields.io/github/actions/workflow/status/grazulex/laravel-snapshot/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Grazulex/laravel-snapshot/actions)
[![Code Style](https://img.shields.io/badge/code%20style-pint-000000?style=flat-square&logo=laravel)](https://github.com/laravel/pint)

## 📖 Table of Contents

- [Overview](#overview)
- [✨ Features](#-features)
- [📦 Installation](#-installation)
- [🚀 Quick Start](#-quick-start)
- [📸 Creating Snapshots](#-creating-snapshots)
- [🔄 Restoring Data](#-restoring-data)
- [📊 Diff Analysis](#-diff-analysis)
- [⚙️ Configuration](#️-configuration)
- [📚 Documentation](#-documentation)
- [💡 Examples](#-examples)
- [🧪 Testing](#-testing)
- [🔧 Requirements](#-requirements)
- [🚀 Performance](#-performance)
- [🤝 Contributing](#-contributing)
- [🔒 Security](#-security)
- [📄 License](#-license)

## Overview

Laravel Snapshot is an advanced model versioning and snapshot system that provides comprehensive change tracking, point-in-time snapshots, and restoration capabilities for Laravel applications. Perfect for audit trails, data recovery, and version control of your Eloquent models.

**Perfect for financial applications, content management systems, and any application requiring detailed audit trails and data recovery.**

### 🎯 Use Cases

Laravel Snapshot is perfect for:

- **Financial Systems** - Transaction history and audit trails
- **Content Management** - Version control for articles and pages
- **E-commerce** - Product and order history tracking  
- **Data Recovery** - Point-in-time data restoration
- **Compliance** - Regulatory audit trail requirements

## ✨ Features

- 🚀 **Automatic Snapshots** - Automatic model state capturing on changes
- 📸 **Manual Snapshots** - Create snapshots at specific points in time
- 🔄 **Easy Restoration** - Restore models to any previous state
- 📊 **Diff Analysis** - Detailed comparison between model versions
- 🎯 **Selective Tracking** - Track only specific model attributes
- 📋 **Metadata Support** - Store additional context with snapshots
- 🔍 **Advanced Querying** - Query snapshots by date, user, or criteria
- 🎨 **Relationship Tracking** - Track changes in model relationships
- ✅ **Validation** - Validate snapshot integrity and consistency
- 📈 **Performance Optimized** - Efficient storage and retrieval
- 🧪 **Testing Support** - Built-in testing utilities
- ⚡ **Batch Operations** - Handle bulk snapshot operations

## 📦 Installation

Install the package via Composer:

```bash
composer require grazulex/laravel-snapshot
```

> **💡 Auto-Discovery**  
> The service provider will be automatically registered thanks to Laravel's package auto-discovery.

Publish configuration:

```bash
php artisan vendor:publish --tag=snapshot-config
```

Publish migrations:

```bash
php artisan vendor:publish --tag=snapshot-migrations
php artisan migrate
```

## 🚀 Quick Start

### 1. Add the Trait to Your Model

```php
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;

class User extends Model
{
    use HasSnapshots;
    
    // Optionally specify which attributes to track
    protected $snapshotable = ['name', 'email', 'status'];
    
    // Exclude sensitive attributes
    protected $snapshotExclude = ['password', 'remember_token'];
}
```

### 2. Configure Automatic Snapshots

```php
// Automatic snapshots on model events
class User extends Model
{
    use HasSnapshots;
    
    protected $snapshotEvents = ['created', 'updated', 'deleted'];
    
    // Custom snapshot triggers
    protected $snapshotTriggers = [
        'status_changed' => function ($model) {
            return $model->isDirty('status');
        }
    ];
}
```

### 3. Create and Manage Snapshots

```php
$user = User::find(1);

// Create manual snapshot
$snapshot = $user->createSnapshot('Before important update');

// Update the model
$user->update(['status' => 'active', 'email' => 'new@example.com']);

// Get all snapshots
$snapshots = $user->snapshots()->orderBy('created_at', 'desc')->get();

// Get specific snapshot
$latestSnapshot = $user->latestSnapshot();
$firstSnapshot = $user->firstSnapshot();
```

### 4. Restore Previous States

```php
// Restore to latest snapshot
$user->restoreFromLatestSnapshot();

// Restore to specific snapshot
$user->restoreFromSnapshot($snapshot);

// Restore to specific date
$user->restoreToDate(now()->subDays(7));

// Preview restoration (without saving)
$previewData = $user->previewRestore($snapshot);
```

## 📸 Creating Snapshots

Laravel Snapshot provides flexible snapshot creation options:

```php
// Basic snapshot
$user->createSnapshot();

// Snapshot with description
$user->createSnapshot('User activated premium account');

// Snapshot with metadata
$user->createSnapshot('Status change', [
    'triggered_by' => auth()->id(),
    'reason' => 'Admin approval',
]);

// Conditional snapshots
$user->createSnapshotIf($user->isDirty('email'), 'Email updated');
```

## 🔄 Restoring Data

Comprehensive data restoration capabilities:

```php
// Simple restoration
$user->restoreFromSnapshot($snapshot);

// Restoration with validation
$user->restoreFromSnapshot($snapshot, ['validate' => true]);

// Selective restoration (only specific attributes)
$user->restoreFromSnapshot($snapshot, ['only' => ['name', 'status']]);

// Restore to specific date
$user->restoreToDate(now()->subDays(7));
```

## 📊 Diff Analysis

Detailed comparison and analysis tools:

```php
use Grazulex\LaravelSnapshot\Analysis\Differ;

$user = User::find(1);

// Compare current state with snapshot
$diff = $user->diffWithSnapshot($snapshot);

foreach ($diff->getChanges() as $attribute => $change) {
    echo "Attribute: {$attribute}\n";
    echo "Old value: {$change['old']}\n";
    echo "New value: {$change['new']}\n";
    echo "Change type: {$change['type']}\n"; // added, modified, removed
}

// Compare two snapshots
$diff = Differ::compare($snapshot1, $snapshot2);

// Visual diff output
echo $diff->toHtml(); // HTML formatted diff
echo $diff->toMarkdown(); // Markdown formatted diff

// Diff statistics
$stats = $diff->getStats();
echo "Total changes: {$stats['total']}\n";
echo "Added: {$stats['added']}\n";
echo "Modified: {$stats['modified']}\n";
echo "Removed: {$stats['removed']}\n";
```

## ⚙️ Configuration

Laravel Snapshot provides extensive configuration options:

```php
// config/snapshot.php
return [
    'storage' => [
        'driver' => 'database', // database, file, s3
        'table' => 'model_snapshots',
        'compress' => true,
    ],
    
    'automatic' => [
        'enabled' => true,
        'events' => ['created', 'updated'],
        'throttle' => '1 minute', // Prevent duplicate snapshots
    ],
    
    'retention' => [
        'enabled' => true,
        'keep_snapshots' => 100,
        'keep_for_days' => 365,
    ],
    
    'features' => [
        'track_relationships' => true,
        'track_metadata' => true,
        'validate_integrity' => true,
    ],
];
```

## 📚 Documentation

For detailed documentation, examples, and advanced usage:

- 📚 [Full Documentation](docs/README.md)
- 🎯 [Examples](examples/README.md)
- 🔧 [Configuration](docs/configuration.md)
- 🧪 [Testing](docs/testing.md)
- 📊 [Diff Analysis](docs/diff-analysis.md)

## 💡 Examples

### Advanced Snapshot Management

```php
use Grazulex\LaravelSnapshot\Facades\Snapshot;

// Batch snapshot creation
Snapshot::batch(function () {
    $users = User::where('created_at', '>=', now()->subDays(7))->get();
    
    foreach ($users as $user) {
        $user->createSnapshot('Weekly backup');
    }
});

// Snapshot with complex metadata
$order = Order::find(1);
$order->createSnapshot('Order processed', [
    'processor' => auth()->user()->name,
    'location' => request()->header('X-Location'),
    'version' => config('app.version'),
    'environment' => app()->environment(),
]);

// Conditional restoration
$user = User::find(1);
$snapshot = $user->snapshots()->where('description', 'Before migration')->first();

if ($snapshot && $user->shouldRestore($snapshot)) {
    $user->restoreFromSnapshot($snapshot);
}
```

### Audit Trail Implementation

```php
// Custom audit trail using snapshots
class AuditTrail
{
    public static function track($model, $action)
    {
        $model->createSnapshot("Audit: {$action}", [
            'audit_action' => $action,
            'user_id' => auth()->id(),
            'timestamp' => now(),
            'session_id' => session()->getId(),
        ]);
    }
    
    public static function getAuditLog($model)
    {
        return $model->snapshots()
            ->whereJsonContains('metadata->audit_action', '!=', null)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

// Usage
AuditTrail::track($user, 'login');
AuditTrail::track($user, 'profile_update');
$auditLog = AuditTrail::getAuditLog($user);
```

### Data Recovery Workflow

```php
// Data recovery service
class DataRecoveryService
{
    public function recoverToDate($model, $date)
    {
        $snapshot = $model->snapshots()
            ->where('created_at', '<=', $date)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($snapshot) {
            return $model->restoreFromSnapshot($snapshot);
        }
        
        throw new Exception('No snapshot found for the specified date');
    }
    
    public function previewRecovery($model, $snapshot)
    {
        $current = $model->toArray();
        $restored = $snapshot->data;
        
        return [
            'current' => $current,
            'restored' => $restored,
            'changes' => array_diff_assoc($restored, $current),
        ];
    }
}
```

Check out the [examples directory](examples) for more examples.

## 🧪 Testing

Laravel Snapshot includes comprehensive testing utilities:

```php
use Grazulex\LaravelSnapshot\Testing\SnapshotTester;

public function test_model_snapshot_creation()
{
    $user = User::factory()->create();
    
    SnapshotTester::make($user)
        ->createSnapshot('Test snapshot')
        ->assertSnapshotExists()
        ->assertSnapshotCount(1)
        ->assertSnapshotContains(['name', 'email']);
}

public function test_model_restoration()
{
    $user = User::factory()->create(['status' => 'inactive']);
    $snapshot = $user->createSnapshot();
    
    $user->update(['status' => 'active']);
    
    SnapshotTester::make($user)
        ->restoreFromSnapshot($snapshot)
        ->assertRestoredSuccessfully()
        ->assertAttribute('status', 'inactive');
}
```

## 🔧 Requirements

- PHP: ^8.3
- Laravel: ^12.0
- Carbon: ^3.10

## 🚀 Performance

Laravel Snapshot is optimized for performance:

- **Efficient Storage**: Optimized snapshot data compression
- **Smart Caching**: Intelligent snapshot caching strategies
- **Batch Operations**: Efficient bulk snapshot processing
- **Query Optimization**: Optimized database queries for large datasets

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 🔒 Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) before disclosing it.

## 📄 License

Laravel Snapshot is open-sourced software licensed under the [MIT license](LICENSE.md).

---

**Made with ❤️ for the Laravel community**

### Resources

- [📖 Documentation](docs/README.md)
- [💬 Discussions](https://github.com/Grazulex/laravel-snapshot/discussions)
- [🐛 Issue Tracker](https://github.com/Grazulex/laravel-snapshot/issues)
- [📦 Packagist](https://packagist.org/packages/grazulex/laravel-snapshot)

### Community Links

- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) - Our code of conduct
- [CONTRIBUTING.md](CONTRIBUTING.md) - How to contribute
- [SECURITY.md](SECURITY.md) - Security policy
- [RELEASES.md](RELEASES.md) - Release notes and changelog
