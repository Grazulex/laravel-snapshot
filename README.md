# Laravel Snapshot

<div align="center">
  <img src="new_logo.png" alt="Laravel Snapshot" width="100">
  <p><strong>Track, store and compare snapshots of your Eloquent models — cleanly and safely.</strong></p>

  [![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-snapshot)](https://packagist.org/packages/grazulex/laravel-snapshot)
  [![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-snapshot)](https://packagist.org/packages/grazulex/laravel-snapshot)
  [![License](https://img.shields.io/github/license/grazulex/laravel-snapshot)](LICENSE.md)
  [![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://php.net)
  [![Laravel Version](https://img.shields.io/badge/laravel-%5E12.19-red)](https://laravel.com)
  [![Code Style](https://img.shields.io/badge/code%20style-pint-orange)](https://github.com/laravel/pint)
</div>

> [!WARNING]
> **🚧 Package en construction** - Ce package est actuellement en développement et n'est pas encore prêt pour la production. L'API peut changer sans préavis.

## Overview

<div style="background: linear-gradient(135deg, #FF9900 0%, #D2D200 25%, #88C600 75%, #00B470 100%); padding: 20px; border-radius: 10px; margin: 20px 0; color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">

**Laravel Snapshot** lets you capture and store the state of any Eloquent model (or group of models) at a specific point in time — for traceability, diffing, testing, or audit purposes.

</div>

## ✨ Features

- 📸 Store snapshots of any model or model group
- 🔁 Compare before/after snapshots
- 📂 Store as JSON, array, file or DB
- 🧪 Perfect for testing or debugging changes
- ✅ Full CLI support for snapshot creation and diff
- 🧠 Smart serialization of relationships, casts, hidden fields
- 📦 Optional database table for persistent storage

## 🛠 Usage Example

```php
use LaravelSnapshot\Snapshot;

Snapshot::save($order, 'before-discount');
Snapshot::save($order->fresh(), 'after-discount');

$diff = Snapshot::diff('before-discount', 'after-discount');

dd($diff);
```

## 📦 CLI Commands

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
