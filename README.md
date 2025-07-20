# Laravel Snapshot

<div align="center">
  <img src="new_logo.png" alt="Laravel Snapshot" width="100">
  <p><strong>Track, store and compare snapshots of your Eloquent models — cleanly and safely.</strong></p>

  [![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-snapshot)](https://packagist.org/packages/grazulex/laravel-snapshot)
  [![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-snapshot)](https://packagist.org/packages/grazulex/laravel-snapshot)
  [![License](https://img.shields.io/github/license/grazulex/laravel-snapshot)](LICENSE.md)
  [![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://php.net)
  [![Laravel Version](https://img.shields.io/badge/laravel-%5E12.19-red)](https://laravel.com)
  [![Tests](https://github.com/Grazulex/laravel-snapshot/workflows/Tests/badge.svg)](https://github.com/Grazulex/laravel-snapshot/actions)
  [![Code Style](https://img.shields.io/badge/code%20style-pint-orange)](https://github.com/laravel/pint)

</div>

## Overview

**Laravel Snapshot** lets you capture and store the state of any Eloquent model (or group of models) at a specific point in time — for traceability, diffing, testing, or audit purposes.

## 📋 Table of Contents

- [✨ Features](#-features)
- [🚀 Quick Start](#-quick-start)
- [📦 CLI Commands](#-cli-commands)
- [📊 Advanced Features](#-advanced-features)
- [💾 Storage Backends](#-storage-backends)
- [⚙️ Configuration](#️-configuration)
- [🧠 Use Cases](#-use-cases)
- [🧪 Testing Support](#-testing-support)
- [📚 Documentation & Examples](#-documentation--examples)
- [🔧 Requirements](#-requirements)
- [🚀 Performance](#-performance)
- [🔒 Security Features](#-security-features)
- [🤝 Contributing](#-contributing)
- [📄 License](#-license)

## ✨ Features

- 📸 **Manual snapshots** - Capture model state on demand
- 🔄 **Automatic snapshots** - Auto-capture on create/update/delete events  
- ⏰ **Scheduled snapshots** - Cron-based periodic snapshots
- 📊 **Smart comparison** - Deep diff between any two snapshots
- 📂 **Multiple storage** - File, database, or memory storage
- 📈 **Rich reports** - Timeline, history, and analytics in HTML/JSON/CSV
- 🎯 **Model tracking** - Full audit trail for any Eloquent model
- 🧪 **Testing support** - Perfect for debugging and testing
- ✅ **CLI commands** - Full command-line interface with advanced options
- 🧠 **Smart serialization** - Handles relationships, casts, hidden fields
- 📊 **Statistics & Analytics** - Change frequency, counters, most changed fields, event analysis
- 🔍 **Model restoration** - Restore models to previous snapshot states with safety features
- ⚡ **High performance** - Optimized for production use with configurable retention
- 🛡️ **Security-first** - Configurable field exclusion and access control
- 🎨 **Flexible reporting** - Multiple output formats with customizable templates
- 🔧 **Advanced configuration** - Comprehensive config for all aspects
- 📦 **Batch operations** - Process multiple snapshots efficiently
- 🎛️ **Console tools** - Rich CLI with dry-run, confirmation, and filtering options

## 🚀 Quick Start

### Installation

```bash
# Install the package
composer require grazulex/laravel-snapshot

# Publish config (optional)
php artisan vendor:publish --tag=snapshot-config

# Publish migrations (if using database storage)
php artisan vendor:publish --tag=snapshot-migrations

# Run migrations
php artisan migrate
```

### Basic Usage

```php
use Grazulex\LaravelSnapshot\Snapshot;

// Create snapshots
Snapshot::save($order, 'before-discount');
Snapshot::save($order->fresh(), 'after-discount');

// Compare snapshots
$diff = Snapshot::diff('before-discount', 'after-discount');
dd($diff);
```

### Using the HasSnapshots Trait

```php
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;

class Order extends Model
{
    use HasSnapshots;
    
    // Auto-snapshots on create, update, delete
    // Configure in config/snapshot.php
}

// Use convenient methods
$order->snapshot('order-created');
$timeline = $order->getSnapshotTimeline();
$report = $order->getHistoryReport('html');
```

## 📦 CLI Commands

### Basic Commands
```bash
# Manual snapshots  
php artisan snapshot:save "App\Models\Order" --id=123 --label=before-shipping

# Compare snapshots
php artisan snapshot:diff before-shipping after-shipping

# List all snapshots with filtering
php artisan snapshot:list --model="App\Models\Order" --event=manual --limit=50

# Generate comprehensive reports
php artisan snapshot:report --model="App\Models\Order" --id=123 --format=html

# Model restoration with safety features
php artisan snapshot:restore "App\Models\Order" 123 "backup-snapshot" --dry-run

# Scheduled snapshots (for cron jobs)
php artisan snapshot:schedule "App\Models\User" --limit=100

# Clear snapshots with filtering
php artisan snapshot:clear --model=Order --older-than=30 --dry-run
```

## 📊 Advanced Features

### Statistics & Analytics
```php
// Get comprehensive statistics
$stats = Snapshot::stats($order)
    ->counters()                    // Basic snapshot counts
    ->mostChangedFields()           // Fields that change most often
    ->changeFrequency()             // Changes over time periods
    ->eventTypeAnalysis()           // Detailed event analysis
    ->get();

// Example results:
$stats = [
    'total_snapshots' => 150,
    'snapshots_by_event' => ['manual' => 45, 'updated' => 85, 'created' => 20],
    'most_changed_fields' => ['status' => 67, 'total' => 34, 'notes' => 28],
    'changes_by_day' => ['2024-07-19' => 12, '2024-07-18' => 8],
    'changes_by_week' => ['2024-29' => 45, '2024-28' => 38],
    'changes_by_month' => ['2024-07' => 89, '2024-06' => 61],
    'average_changes_per_day' => 3.2,
    'event_type_percentages' => ['updated' => 56.7, 'manual' => 30.0, 'created' => 13.3],
    'most_recent_by_event_type' => ['updated' => '2024-07-19 14:30:00', 'manual' => '2024-07-19 10:15:00']
];

// Get statistics as JSON
$jsonStats = Snapshot::stats($order)->counters()->changeFrequency()->toJson();
```

### Timeline & History
```php
// Get detailed timeline with metadata
$timeline = $order->getSnapshotTimeline(50); // Last 50 snapshots
// Returns: [['id' => 1, 'label' => '...', 'event_type' => '...', 'created_at' => '...', 'data' => [...]], ...]

// Generate reports in multiple formats
$htmlReport = $order->getHistoryReport('html');    // Rich HTML with styling and diffs
$jsonReport = $order->getHistoryReport('json');    // Structured data for APIs  
$csvReport = $order->getHistoryReport('csv');      // Tabular format for analysis

// Advanced report generation with custom options
$report = SnapshotReport::for($order)
    ->format('html')
    ->options(['include_diffs' => true, 'max_entries' => 100])
    ->generate();

// Get latest snapshot and compare with current state
$latestSnapshot = $order->getLatestSnapshot();
$currentDiff = $order->compareWithSnapshot($latestSnapshot['id']);
```

### Scheduled Snapshots
```php
// Manual scheduled snapshot creation
$result = Snapshot::scheduled($user, 'daily-backup-2024-07-19');

// Via console command (ideal for cron jobs)
php artisan snapshot:schedule "App\Models\User" --limit=100 --label=daily
php artisan snapshot:schedule "App\Models\Order" --id=123 --label=backup

// Add to your crontab or Laravel scheduler:
// 0 2 * * * php artisan snapshot:schedule "App\Models\User" --limit=1000
// 0 */6 * * * php artisan snapshot:schedule "App\Models\Order" --limit=500

// Configure in schedule (app/Console/Kernel.php)
$schedule->command('snapshot:schedule', ['App\Models\User', '--limit=1000'])
         ->dailyAt('02:00');
$schedule->command('snapshot:schedule', ['App\Models\Order', '--limit=500'])  
         ->everySixHours();
```

### Model Restoration
```php
// Restore model to previous snapshot state
$snapshot = $order->snapshots()->first();
$success = $order->restoreFromSnapshot($snapshot->id);

// Or restore by snapshot label
$success = $order->restoreFromSnapshot('before-important-change');

// Compare current state with previous snapshot before restoring
$diff = $order->compareWithSnapshot('backup-snapshot');
if (!empty($diff['modified'])) {
    echo "Changes would be applied:\n";
    foreach ($diff['modified'] as $field => $change) {
        echo "- {$field}: {$change['from']} → {$change['to']}\n";
    }
}

// Restore via console command with safety features
php artisan snapshot:restore "App\Models\Order" 123 "backup-snapshot" --dry-run
php artisan snapshot:restore "App\Models\Order" 123 "backup-snapshot" --force
```

## 💾 Storage Backends

### Database Storage (Default)
- **Best for**: Production applications with frequent querying
- **Features**: Fast queries, relationships, built-in indexing
- **Configuration**: Automatic, uses `snapshots` table

### File Storage
- **Best for**: Archival storage, backup scenarios
- **Features**: Human-readable JSON files, easy backup
- **Configuration**: Set `SNAPSHOT_DRIVER=file` in .env

```php
// config/snapshot.php
'default' => 'file',
'drivers' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('snapshots'),
    ],
],
```

### Array Storage (Testing)
- **Best for**: Unit tests and development
- **Features**: In-memory, no persistence
- **Usage**: Automatically cleared between tests

```php
// In tests
Snapshot::setStorage(new ArrayStorage());
```

## ⚙️ Configuration

### Core Storage Settings
```php
// config/snapshot.php
'default' => 'database', // Default storage driver

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

### Serialization Options
```php
'serialization' => [
    'include_hidden' => false,           // Include hidden model attributes
    'include_timestamps' => true,        // Include created_at/updated_at
    'include_relationships' => true,     // Include loaded relationships
    'max_relationship_depth' => 3,       // Maximum depth for nested relationships
],
```

### Automatic Snapshots
```php
'automatic' => [
    'enabled' => true,
    'events' => ['created', 'updated', 'deleted'],
    'exclude_fields' => ['updated_at', 'password', 'remember_token'],
    'models' => [
        'App\Models\Order' => ['created', 'updated', 'deleted'],
        'App\Models\User' => ['created', 'updated'],
        'App\Models\Payment' => ['created'],
    ],
],
```

### Scheduled Snapshots
```php
'scheduled' => [
    'enabled' => true,
    'default_frequency' => 'daily',
    'models' => [
        'App\Models\User' => 'daily',
        'App\Models\Order' => 'hourly',
        'App\Models\Invoice' => 'weekly',
    ],
],
```

### Retention Policy
```php
'retention' => [
    'enabled' => true,
    'days' => 30,              // Keep snapshots for 30 days
    'auto_cleanup' => true,    // Automatically clean up old snapshots
],
```

### Reports & Analytics
```php
'reports' => [
    'enabled' => true,
    'formats' => ['html', 'json', 'csv'],
    'template' => 'default',
    'max_timeline_entries' => 100,
    'include_diffs' => true,
],
```

## 🧠 Use Cases

### E-commerce
- Snapshot orders before and after processing
- Track price changes and discounts  
- Monitor inventory levels over time
- Audit payment transactions
- **NEW**: Restore orders to previous states if processing errors occur

### Content Management
- Version control for articles and pages
- Track editorial changes and approvals
- Backup content before major updates
- Compare content versions
- **NEW**: Generate change reports for editorial workflow

### User Management
- Audit trail for profile changes
- Track permission and role updates
- Monitor sensitive data modifications
- Compliance and security auditing
- **NEW**: Analyze user behavior patterns through snapshot statistics

### Financial Applications
- Snapshot account balances before transactions
- Track investment portfolio changes
- Audit financial calculations
- Regulatory compliance reporting
- **NEW**: Automated restoration procedures for transaction rollbacks

### Development & Testing
- Debug model state changes during development
- Verify expected changes in feature tests
- Compare before/after states in CI/CD
- Rollback safety during deployments
- **NEW**: Performance analysis of data changes over time

## 🧪 Testing Support

### Feature Testing
```php
public function test_order_processing()
{
    // Create initial snapshot
    Snapshot::save($order, 'initial');
    
    // Process the order
    $this->orderProcessor->process($order);
    
    // Verify changes
    Snapshot::save($order, 'processed');
    $diff = Snapshot::diff('initial', 'processed');
    
    $this->assertArrayHasKey('modified', $diff);
    $this->assertEquals('completed', $diff['modified']['status']['to']);
}
```

### Unit Testing
```php
public function setUp(): void
{
    parent::setUp();
    
    // Use in-memory storage for tests
    Snapshot::setStorage(new ArrayStorage());
}
```

## 📚 Documentation & Examples

- **[Complete Documentation](docs/README.md)** - Comprehensive guides and API reference
- **[Getting Started Guide](docs/getting-started.md)** - Quick start tutorial
- **[Configuration Guide](docs/configuration.md)** - Detailed configuration options
- **[Basic Usage](docs/basic-usage.md)** - Core features and examples
- **[Console Commands](docs/console-commands.md)** - CLI reference
- **[API Reference](docs/api-reference.md)** - Complete API documentation

### Practical Examples

- **[Basic Usage Example](examples/basic-usage.php)** - Simple snapshot operations
- **[E-commerce Order Processing](examples/ecommerce-order-processing.php)** - Real-world order tracking
- **[Model with HasSnapshots Trait](examples/model-with-trait.php)** - Trait integration
- **[More Examples →](examples/README.md)**

## 🔧 Requirements

- **PHP**: ^8.3
- **Laravel**: ^12.19
- **Carbon**: ^3.10
- **Database**: MySQL, PostgreSQL, SQLite, or SQL Server

## 🚀 Performance

Laravel Snapshot is designed for production use:

- **Efficient Storage**: Minimal database impact with optimized schemas
- **Smart Serialization**: Configurable field inclusion/exclusion  
- **Bulk Operations**: Process multiple snapshots efficiently
- **Memory Management**: Handles large models without memory issues
- **Query Optimization**: Indexed lookups and efficient comparisons

## 🔒 Security Features

- **Field Exclusion**: Automatically exclude sensitive fields (passwords, tokens)
- **Access Control**: Integrate with your application's authorization
- **Data Encryption**: Optional encryption for sensitive snapshot data
- **Audit Logging**: Track who creates and accesses snapshots

## <span style="color: #88C600;">🤝</span> Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## <span style="color: #FF9900;">🔒</span> Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) before disclosing it.

## <span style="color: #FF9900;">📄</span> License

Laravel Snapshot is open-sourced software licensed under the [MIT license](LICENSE.md).

---

<div align="center">
  Made with <span style="color: #FF9900;">❤️</span> for the <span style="color: #88C600;">Laravel</span> community
</div>
