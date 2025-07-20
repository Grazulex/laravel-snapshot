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

## ✨ Features

- 📸 **Manual snapshots** - Capture model state on demand
- 🔄 **Automatic snapshots** - Auto-capture on create/update/delete events  
- ⏰ **Scheduled snapshots** - Cron-based periodic snapshots
- 📊 **Smart comparison** - Deep diff between any two snapshots
- 📂 **Multiple storage** - File, database, or memory storage
- 📈 **Rich reports** - Timeline, history, and analytics
- 🎯 **Model tracking** - Full audit trail for any Eloquent model
- 🧪 **Testing support** - Perfect for debugging and testing
- ✅ **CLI commands** - Full command-line interface
- 🧠 **Smart serialization** - Handles relationships, casts, hidden fields
- 📊 **Statistics & Analytics** - Change frequency, counters, most changed fields
- 🔍 **Model restoration** - Restore models to previous snapshot states
- ⚡ **High performance** - Optimized for production use
- 🛡️ **Security-first** - Configurable field exclusion and access control

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

# List all snapshots
php artisan snapshot:list

# Generate reports
php artisan snapshot:report --model="App\Models\Order" --id=123

# Clear snapshots
php artisan snapshot:clear --model=Order
```

## 📊 Advanced Features

### Statistics & Analytics
```php
// Get comprehensive statistics
$stats = Snapshot::stats($order)
    ->counters()
    ->mostChangedFields()
    ->changeFrequency()
    ->get();

// Results include:
// - Total snapshots count
// - Snapshots by event type
// - Most frequently changed fields  
// - Change frequency by day/week/month
```

### Timeline & History
```php
// Get detailed timeline
$timeline = $order->getSnapshotTimeline(50); // Last 50 snapshots

// Generate reports in multiple formats
$htmlReport = $order->getHistoryReport('html');
$jsonReport = $order->getHistoryReport('json');
$csvReport = $order->getHistoryReport('csv');
```

### Model Restoration
```php
// Restore model to previous state
$snapshot = $order->snapshots()->first();
$order->restoreFromSnapshot($snapshot->id);
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

### Automatic Snapshots
```php
// config/snapshot.php
'automatic' => [
    'enabled' => true,
    'models' => [
        'App\Models\Order' => ['created', 'updated', 'deleted'],
        'App\Models\User' => ['created', 'updated'],
        'App\Models\Payment' => ['created'],
    ],
    'exclude_fields' => ['updated_at', 'password', 'remember_token'],
],
```

### Scheduled Snapshots
```php
'scheduled' => [
    'enabled' => true,
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
    'days' => 30,        // Keep snapshots for 30 days
    'auto_cleanup' => true,
],
```

## 🧠 Use Cases

### E-commerce
- Snapshot orders before and after processing
- Track price changes and discounts  
- Monitor inventory levels over time
- Audit payment transactions

### Content Management
- Version control for articles and pages
- Track editorial changes and approvals
- Backup content before major updates
- Compare content versions

### User Management
- Audit trail for profile changes
- Track permission and role updates
- Monitor sensitive data modifications
- Compliance and security auditing

### Financial Applications
- Snapshot account balances before transactions
- Track investment portfolio changes
- Audit financial calculations
- Regulatory compliance reporting

### Development & Testing
- Debug model state changes during development
- Verify expected changes in feature tests
- Compare before/after states in CI/CD
- Rollback safety during deployments

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

## 🛡️ Security

- **Field Exclusion**: Automatically exclude sensitive fields (passwords, tokens)
- **Access Control**: Integrate with your application's authorization
- **Data Encryption**: Optional encryption for sensitive snapshot data
- **Audit Logging**: Track who creates and accesses snapshots

---

<div align="center">
  Made with <span style="color: #FF9900;">❤️</span> for the <span style="color: #88C600;">Laravel</span> community
</div>
