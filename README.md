# Laravel Snapshot

<div align="center">
  <img src="new_logo.png" alt="Laravel Snapshot" width="200">
  <p><strong>Advanced model versioning and snapshot system for Laravel applications</strong></p>

  [![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-snapshot.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-snapshot)
  [![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-snapshot.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-snapshot)
  [![License](https://img.shields.io/github/license/grazulex/laravel-snapshot.svg?style=flat-square)](https://github.com/Grazulex/laravel-snapshot/blob/main/LICENSE.md)
  [![PHP Version](https://img.shields.io/packagist/php-v/grazulex/laravel-snapshot.svg?style=flat-square)](https://php.net/)
  [![Laravel Version](https://img.shields.io/badge/laravel-12.x-ff2d20?style=flat-square&logo=laravel)](https://laravel.com/)
  [![Tests](https://img.shields.io/github/actions/workflow/status/grazulex/laravel-snapshot/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Grazulex/laravel-snapshot/actions)
  [![Code Style](https://img.shields.io/badge/code%20style-pint-000000?style=flat-square&logo=laravel)](https://github.com/laravel/pint)

</div>

## Overview

Laravel Snapshot is a powerful package for tracking, storing and comparing snapshots of your Eloquent models — cleanly and safely. Perfect for audit trails, data recovery, debugging, and version control of your Laravel applications.

## ✨ Features

- � **Manual & Automatic Snapshots** - Capture model state on demand or automatically
- 🔄 **Model Restoration** - Restore models to any previous snapshot state  
- 📊 **Smart Comparisons** - Deep diff analysis between snapshots
- 💾 **Multiple Storage Backends** - Database, file system, or in-memory storage
- 📈 **Rich Analytics** - Statistics, change frequency, and trend analysis
- ⚡ **CLI Commands** - Full command-line interface for all operations
- 🧪 **Testing Support** - Built with Pest 3 and extensive test coverage
- ✅ **Production Ready** - PHPStan level 5, optimized performance

## � Quick Installation

```bash
# Install the package
composer require grazulex/laravel-snapshot

# Publish config (optional)
php artisan vendor:publish --tag=snapshot-config

# Run migrations
php artisan migrate
```

## � Documentation

**All documentation, examples, and guides are now available in our comprehensive Wiki:**

### 📚 [**Visit the Laravel Snapshot Wiki →**](https://github.com/Grazulex/laravel-snapshot/wiki)

**Quick Navigation:**

| Topic | Link |
|-------|------|
| 🏁 **Getting Started** | [Installation & Setup](https://github.com/Grazulex/laravel-snapshot/wiki/Installation) |
| 📘 **Basic Usage** | [Creating & Managing Snapshots](https://github.com/Grazulex/laravel-snapshot/wiki/Basic-Usage) |
| ⚙️ **Configuration** | [Configuration Options](https://github.com/Grazulex/laravel-snapshot/wiki/Configuration) |
| 🔄 **Model Restoration** | [Restoring Previous States](https://github.com/Grazulex/laravel-snapshot/wiki/Model-Restoration) |
| 📊 **Analytics & Reports** | [Statistics & Analytics](https://github.com/Grazulex/laravel-snapshot/wiki/Analytics) |
| ⚡ **CLI Commands** | [Command Reference](https://github.com/Grazulex/laravel-snapshot/wiki/CLI-Commands) |
| 💡 **Examples** | [Real-world Examples](https://github.com/Grazulex/laravel-snapshot/wiki/Examples) |
| 🧪 **Testing** | [Testing Your Implementation](https://github.com/Grazulex/laravel-snapshot/wiki/Testing) |
| 🔧 **Advanced Usage** | [Advanced Features](https://github.com/Grazulex/laravel-snapshot/wiki/Advanced-Usage) |
| 🚀 **API Reference** | [Complete API Documentation](https://github.com/Grazulex/laravel-snapshot/wiki/API-Reference) |

## 💡 Quick Example

```php
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;

class Order extends Model
{
    use HasSnapshots;
}

// Create snapshots
$order = Order::find(1);
$order->snapshot('before-discount');

$order->update(['total' => 99.99]);
$order->snapshot('after-discount');

// Compare and restore
$diff = $order->compareWithSnapshot('before-discount');
$order->restoreFromSnapshot('before-discount');

// CLI usage
php artisan snapshot:save "App\Models\Order" --id=1 --label=backup
php artisan snapshot:restore "App\Models\Order" 1 backup
php artisan snapshot:diff before-discount after-discount
```

## 🎯 Use Cases

Perfect for:

- **Financial Systems** - Transaction history and audit trails
- **Content Management** - Version control for articles and pages  
- **E-commerce** - Product and order change tracking
- **Data Recovery** - Point-in-time data restoration
- **Compliance** - Regulatory audit trail requirements
- **Debugging** - Track state changes during development

## 🔧 Requirements

- **PHP**: ^8.3
- **Laravel**: ^12.19
- **Carbon**: ^3.10

## 🧪 Quality Assurance

- ✅ **95 Tests** passing with Pest 3
- ✅ **PHPStan Level 5** compliance
- ✅ **60%+ Code Coverage**
- ✅ **Laravel Pint** code style
- ✅ **Comprehensive CLI** testing

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Commands

```bash
composer run-script full    # Run all quality checks
composer run-script test    # Run tests
composer run-script pint    # Fix code style
composer run-script phpstan # Static analysis
```

## 🔒 Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md).

## 📄 License

Laravel Snapshot is open-sourced software licensed under the [MIT license](LICENSE.md).

---

<div align="center">

**📚 [Complete Documentation](https://github.com/Grazulex/laravel-snapshot/wiki) | 💬 [Discussions](https://github.com/Grazulex/laravel-snapshot/discussions) | � [Issues](https://github.com/Grazulex/laravel-snapshot/issues)**

Made with ❤️ for the Laravel community

</div>
