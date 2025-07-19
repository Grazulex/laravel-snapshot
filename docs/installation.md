# Installation

## Requirements

Before installing Laravel Snapshot, make sure your system meets these requirements:

- **PHP**: ^8.3
- **Laravel**: ^12.19  
- **Carbon**: ^3.10
- **Database**: MySQL, PostgreSQL, SQLite, or SQL Server (for database storage)

## Step 1: Install via Composer

Install the package using Composer:

```bash
composer require grazulex/laravel-snapshot
```

## Step 2: Publish Configuration (Optional)

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=snapshot-config
```

This will create `config/snapshot.php` where you can configure:
- Storage drivers
- Automatic snapshots
- Retention policies
- Report settings

## Step 3: Run Migrations

Create the snapshots table by running the migrations:

```bash
php artisan migrate
```

This creates a `snapshots` table with the following structure:

```sql
CREATE TABLE snapshots (
    id BIGINT UNSIGNED PRIMARY KEY,
    model_type VARCHAR(255) NOT NULL,
    model_id VARCHAR(255) NOT NULL,
    label VARCHAR(255) NOT NULL UNIQUE,
    event_type VARCHAR(255) NOT NULL,
    data JSON NOT NULL,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_model (model_type, model_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at)
);
```

## Step 4: Service Provider Registration (Auto-Discovery)

The package uses Laravel's auto-discovery feature, so the service provider will be registered automatically. No manual registration needed.

If you've disabled auto-discovery, add this to your `config/app.php`:

```php
'providers' => [
    // Other providers...
    Grazulex\LaravelSnapshot\LaravelSnapshotServiceProvider::class,
],
```

## Verify Installation

Test your installation with a simple command:

```bash
php artisan snapshot:list
```

You should see an empty list (no snapshots yet) without any errors.

## Alternative Storage Configurations

### File Storage Setup

If you prefer file-based storage, update your `.env`:

```env
SNAPSHOT_DRIVER=file
SNAPSHOT_PATH=/path/to/snapshots
```

Or publish and modify the config:

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

Make sure the storage directory is writable:

```bash
mkdir -p storage/snapshots
chmod 755 storage/snapshots
```

### Memory Storage (Testing)

For testing environments, you can use in-memory storage:

```php
// In your tests
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Grazulex\LaravelSnapshot\Snapshot;

// Set up memory storage for tests
Snapshot::setStorage(new ArrayStorage());
```

## Docker Setup

If using Docker, add these to your Dockerfile:

```dockerfile
# Install package (already handled by composer)
# Ensure storage directory exists
RUN mkdir -p /var/www/storage/snapshots && \
    chown -R www-data:www-data /var/www/storage/snapshots
```

## Troubleshooting Installation

### Migration Issues

If migrations fail, check:

1. **Database connection**: Ensure your database is running and configured correctly
2. **Permissions**: Make sure your database user has CREATE TABLE permissions
3. **Existing tables**: If you have a `snapshots` table, you may need to drop it first

### Storage Permission Issues

For file storage, ensure Laravel can write to the snapshots directory:

```bash
# Create directory if it doesn't exist
mkdir -p storage/snapshots

# Set proper permissions
chmod 755 storage/snapshots
chown www-data:www-data storage/snapshots  # On production
```

### Composer Issues

If Composer installation fails:

```bash
# Clear Composer cache
composer clear-cache

# Try again with verbose output
composer require grazulex/laravel-snapshot -v
```

### Configuration Issues

If the package doesn't work as expected:

1. Clear config cache: `php artisan config:clear`
2. Republish config: `php artisan vendor:publish --tag=snapshot-config --force`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`

## Next Steps

Now that Laravel Snapshot is installed, you can:

1. [Configure the package](configuration.md)
2. [Learn basic usage](basic-usage.md)
3. [Try the examples](../examples/README.md)