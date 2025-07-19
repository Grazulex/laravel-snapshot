# Laravel Snapshot Documentation

Welcome to the Laravel Snapshot documentation! This powerful Laravel package allows you to track, store and compare snapshots of your Eloquent models — cleanly and safely.

## Table of Contents

1. [Getting Started](getting-started.md)
2. [Installation](installation.md) 
3. [Configuration](configuration.md)
4. [Basic Usage](basic-usage.md)
5. [API Reference](api-reference.md)
6. [Console Commands](console-commands.md)
7. [Storage Backends](storage-backends.md)
8. [Automatic Snapshots](automatic-snapshots.md)
9. [Reports & Analytics](reports-analytics.md)
10. [Examples](../examples/README.md)
11. [Advanced Usage](advanced-usage.md)
12. [Troubleshooting](troubleshooting.md)

## Quick Start

```php
use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;

// Add trait to your model
class Order extends Model
{
    use HasSnapshots;
}

// Create snapshots
Snapshot::save($order, 'before-discount');
Snapshot::save($order->fresh(), 'after-discount');

// Compare snapshots
$diff = Snapshot::diff('before-discount', 'after-discount');
```

## Features Overview

- 📸 **Manual snapshots** - Capture model state on demand
- 🔄 **Automatic snapshots** - Auto-capture on create/update/delete events  
- ⏰ **Scheduled snapshots** - Cron-based periodic snapshots
- 📊 **Smart comparison** - Deep diff between any two snapshots
- 📂 **Multiple storage** - File, database, or memory storage
- 📈 **Rich reports** - Timeline, history, and analytics
- 🎯 **Model tracking** - Full audit trail for any Eloquent model
- 🧪 **Testing support** - Perfect for debugging and testing
- ✅ **CLI commands** - Full command-line interface

## Requirements

- PHP ^8.3
- Laravel ^12.19
- Carbon ^3.10

## License

This package is open-sourced software licensed under the [MIT license](../LICENSE.md).