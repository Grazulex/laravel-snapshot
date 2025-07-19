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

> [!WARNING]
> **🚧 Work in Progress** - This package is currently under development and is not yet ready for production use. The API may change without notice.

## Overview

<div style="background: linear-gradient(135deg, #FF9900 0%, #D2D200 25%, #88C600 75%, #00B470 100%); padding: 20px; border-radius: 10px; margin: 20px 0; color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">

**Laravel Snapshot** lets you capture and store the state of any Eloquent model (or group of models) at a specific point in time — for traceability, diffing, testing, or audit purposes.

</div>

## ✨ Features

- 📸 **Manual snapshots** - Capture model state on demand
- 🔄 **Automatic snapshots** - Auto-capture on create/update/delete events  
- ⏰ **Scheduled snapshots** - Cron-based periodic snapshots
- � **Smart comparison** - Deep diff between any two snapshots
- 📂 **Multiple storage** - File, database, or memory storage
- 📊 **Rich reports** - Timeline, history, and analytics
- 🎯 **Model tracking** - Full audit trail for any Eloquent model
- 🧪 **Testing support** - Perfect for debugging and testing
- ✅ **CLI commands** - Full command-line interface
- 🧠 **Smart serialization** - Handles relationships, casts, hidden fields

## � Installation

Install the package via Composer:

```bash
composer require grazulex/laravel-snapshot
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=snapshot-config
```

Run the migration to create the snapshots table:

```bash
php artisan migrate
```

## 🛠 Usage Examples

### Manual Snapshots
```php
use Grazulex\LaravelSnapshot\Snapshot;

// Basic snapshot
Snapshot::save($order, 'before-discount');
Snapshot::save($order->fresh(), 'after-discount');

// Compare snapshots
$diff = Snapshot::diff('before-discount', 'after-discount');
dd($diff);
```

### Automatic Snapshots
```php
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;

class Order extends Model
{
    use HasSnapshots;
    
    // Auto-snapshot on create, update, delete
    // Configure in config/snapshot.php
}
```

### Model History & Reports
```php
// Get model timeline
$timeline = $order->getSnapshotTimeline();

// Generate history report
$report = $order->getHistoryReport('html');

// Get change statistics
$stats = Snapshot::stats($order)
    ->counters()
    ->mostChangedFields()
    ->changeFrequency()
    ->get();
```

## 📦 CLI Commands

### Basic Commands
```bash
# Manual snapshots  
php artisan snapshot:save "App\Models\Order" --id=123 --label=before-shipping
php artisan snapshot:diff before-shipping after-shipping
php artisan snapshot:list
```

```bash
php artisan snapshot:save order:123 --label=before-shipping
php artisan snapshot:diff before-shipping after-shipping
php artisan snapshot:list
php artisan snapshot:clear --model=Order
```

## 💾 Storage Backends

- 📁 File-based (JSON per snapshot)
- 🧠 Database table `snapshots`
- 🧪 In-memory (testing mode)

## 🧰 Configuration

```php
return [
    'driver' => 'file', // or 'database'
    'path' => storage_path('app/snapshots'),
];
```

## 🧠 Use Cases

- Snapshot an invoice before signature
- Debug state changes in an order
- Compare model before/after background job
- Validate changes during feature tests
- Provide rollback safety during refactor

## 🧪 Test Support

Use `Snapshot::save()` in your feature tests to verify model state at any step.

```php
Snapshot::save($user, 'after-registration');
```

---

<div align="center">
  Made with <span style="color: #FF9900;">❤️</span> for the <span style="color: #88C600;">Laravel</span> community
</div>
