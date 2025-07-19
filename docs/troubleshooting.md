# Troubleshooting

This guide helps you diagnose and resolve common issues with Laravel Snapshot.

## Installation Issues

### Composer Installation Fails

**Problem**: `composer require grazulex/laravel-snapshot` fails

**Solutions**:

1. **Update Composer**:
```bash
composer self-update
composer clear-cache
```

2. **Check PHP Version**:
```bash
php -v  # Must be ^8.3
```

3. **Check Laravel Version**:
```bash
composer show laravel/framework  # Must be ^12.19
```

4. **Memory Issues**:
```bash
php -d memory_limit=2G composer require grazulex/laravel-snapshot
```

### Migration Issues

**Problem**: `php artisan migrate` fails with snapshot migration

**Error Messages**:
- `SQLSTATE[42S01]: Base table or view already exists: 1050 Table 'snapshots' already exists`
- `SQLSTATE[42000]: Syntax error or access violation`

**Solutions**:

1. **Check Database Connection**:
```bash
php artisan tinker
>>> DB::connection()->getPdo()  # Should not throw error
```

2. **Drop Existing Table** (if needed):
```sql
DROP TABLE IF EXISTS snapshots;
```

3. **Run Migration Again**:
```bash
php artisan migrate:fresh
# Or just the snapshot migration
php artisan migrate --path=vendor/grazulex/laravel-snapshot/database/migrations
```

4. **Check Database Permissions**:
```sql
-- Your database user needs CREATE TABLE permissions
GRANT CREATE, ALTER, DROP ON database_name.* TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

### Service Provider Issues

**Problem**: Package not auto-discovered

**Solution**: Manually register the service provider in `config/app.php`:
```php
'providers' => [
    // Other providers...
    Grazulex\LaravelSnapshot\LaravelSnapshotServiceProvider::class,
],
```

---

## Configuration Issues

### Config File Not Found

**Problem**: Configuration methods don't work, getting default values

**Solutions**:

1. **Publish Config**:
```bash
php artisan vendor:publish --tag=snapshot-config
```

2. **Clear Config Cache**:
```bash
php artisan config:clear
php artisan config:cache
```

3. **Check Config File**:
```bash
# Should exist and be readable
ls -la config/snapshot.php
```

### Storage Driver Issues

**Problem**: Snapshots not being saved or "Driver not found" errors

**Solutions**:

1. **Check Default Driver**:
```php
// config/snapshot.php
'default' => 'database',  // Must match a key in 'drivers' array
```

2. **Validate Driver Configuration**:
```php
'drivers' => [
    'database' => [
        'driver' => 'database',  // Must match the key
        'table' => 'snapshots',
    ],
],
```

3. **Clear Config Cache** after changes:
```bash
php artisan config:clear
```

---

## Storage Issues

### Database Storage Issues

**Problem**: Database snapshots not saving

**Solutions**:

1. **Check Table Exists**:
```sql
DESCRIBE snapshots;
```

2. **Check Table Structure**:
```php
php artisan tinker
>>> Schema::hasTable('snapshots')  # Should return true
>>> Schema::hasColumns('snapshots', ['id', 'label', 'data'])  # Should return true
```

3. **Check Database Permissions**:
```sql
-- Test INSERT permission
INSERT INTO snapshots (model_type, model_id, label, event_type, data, created_at, updated_at) 
VALUES ('Test', '1', 'test', 'manual', '{}', NOW(), NOW());

-- Clean up
DELETE FROM snapshots WHERE label = 'test';
```

### File Storage Issues

**Problem**: File snapshots not saving or permission errors

**Solutions**:

1. **Check Directory Exists**:
```bash
ls -la storage/snapshots
# Should exist and be writable
```

2. **Create Directory**:
```bash
mkdir -p storage/snapshots
chmod 755 storage/snapshots
```

3. **Fix Permissions**:
```bash
# Development
chmod 755 storage/snapshots

# Production  
chown -R www-data:www-data storage/snapshots
chmod 755 storage/snapshots
```

4. **Check Disk Space**:
```bash
df -h storage/snapshots
```

5. **Test File Creation**:
```bash
# Should succeed
touch storage/snapshots/test.json
rm storage/snapshots/test.json
```

---

## Runtime Issues

### Snapshot Creation Fails

**Problem**: `Snapshot::save()` throws exceptions

**Error Messages**:
- `Class 'App\Models\User' not found`
- `Call to undefined method`
- `Serialization failed`

**Solutions**:

1. **Check Model Class**:
```php
// Make sure the model exists and is autoloaded
use App\Models\User;
$user = new User;  # Should not error
```

2. **Check Model Instance**:
```php
// Model must exist in database for snapshots with ID
$user = User::find(1);
if (!$user) {
    echo "User not found";
}
```

3. **Debug Serialization**:
```php
try {
    $serialized = Snapshot::serializeModel($user);
    dd($serialized);
} catch (Exception $e) {
    echo "Serialization error: " . $e->getMessage();
}
```

### Snapshot Loading Fails

**Problem**: `Snapshot::load()` returns null for existing snapshots

**Solutions**:

1. **Check Label Exists**:
```bash
php artisan snapshot:list
# Verify your label is in the list
```

2. **Check Storage Driver**:
```php
// In tinker
>>> config('snapshot.default')  # Should return your expected driver
```

3. **Test Storage Directly**:
```php
// In tinker
$storage = Snapshot::getStorage();  # This will reveal the issue
```

### Diff Comparison Fails

**Problem**: `Snapshot::diff()` throws exceptions

**Solutions**:

1. **Check Both Labels Exist**:
```bash
php artisan snapshot:list | grep -E "(label1|label2)"
```

2. **Test Individual Loads**:
```php
$snap1 = Snapshot::load('label1');
$snap2 = Snapshot::load('label2'); 
if (!$snap1) echo "Label1 not found";
if (!$snap2) echo "Label2 not found";
```

---

## Performance Issues

### Slow Snapshot Operations

**Problem**: Snapshot creation or loading is slow

**Solutions**:

1. **Database Storage Optimization**:
```sql
-- Add indexes if missing
CREATE INDEX idx_snapshots_model ON snapshots(model_type, model_id);
CREATE INDEX idx_snapshots_created ON snapshots(created_at);
CREATE INDEX idx_snapshots_event ON snapshots(event_type);
```

2. **Exclude Large Fields**:
```php
// config/snapshot.php
'automatic' => [
    'exclude_fields' => [
        'large_text_field',
        'binary_data', 
        'computed_field',
        'created_at',
        'updated_at',
    ],
],
```

3. **Limit Relationships**:
```php
'serialization' => [
    'include_relationships' => false,  // Or set to specific relationships
    'max_relationship_depth' => 1,
],
```

4. **Use File Storage for Large Snapshots**:
```php
'default' => 'file',
```

### Memory Issues

**Problem**: Out of memory errors during snapshot operations

**Solutions**:

1. **Increase Memory Limit**:
```php
ini_set('memory_limit', '512M');
```

2. **Exclude Relationships**:
```php
'serialization' => [
    'include_relationships' => false,
],
```

3. **Process in Batches**:
```php
// Instead of snapshotting large collections
$users = User::chunk(100, function ($users) {
    foreach ($users as $user) {
        Snapshot::save($user, "user-{$user->id}");
    }
});
```

---

## Automatic Snapshots Issues

### Automatic Snapshots Not Triggered

**Problem**: Model events don't create snapshots

**Solutions**:

1. **Check Configuration**:
```php
// config/snapshot.php
'automatic' => [
    'enabled' => true,  // Must be true
    'models' => [
        'App\Models\User' => ['created', 'updated', 'deleted'],
    ],
],
```

2. **Clear Config Cache**:
```bash
php artisan config:clear
```

3. **Check Model Uses Trait**:
```php
class User extends Model
{
    use HasSnapshots;  // Required for automatic snapshots
}
```

4. **Test Manual Events**:
```php
// In tinker - should create snapshots
$user = User::create(['name' => 'Test', 'email' => 'test@example.com']);
$user->update(['name' => 'Updated']);
$user->delete();
```

### Too Many Automatic Snapshots

**Problem**: Automatic snapshots creating too many entries

**Solutions**:

1. **Limit Events**:
```php
'automatic' => [
    'models' => [
        'App\Models\User' => ['created'],  // Only on creation
    ],
],
```

2. **Set Up Retention**:
```php
'retention' => [
    'enabled' => true,
    'days' => 7,  // Keep only 7 days
    'auto_cleanup' => true,
],
```

3. **Schedule Cleanup**:
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('snapshot:clear --older-than=30')->daily();
}
```

---

## Command Issues

### Commands Not Found

**Problem**: `php artisan snapshot:*` commands not available

**Solutions**:

1. **Check Package Installation**:
```bash
composer show grazulex/laravel-snapshot
```

2. **List Available Commands**:
```bash
php artisan list snapshot
```

3. **Clear Command Cache**:
```bash
php artisan clear-compiled
php artisan optimize:clear
```

### Command Execution Errors

**Problem**: Commands throw errors or produce unexpected output

**Solutions**:

1. **Check Verbose Output**:
```bash
php artisan snapshot:save "App\Models\User" --id=1 -v
```

2. **Check Log Files**:
```bash
tail -f storage/logs/laravel.log
```

3. **Test in Tinker First**:
```php
// If this works in tinker, command should work too
$user = User::find(1);
Snapshot::save($user, 'test');
```

---

## Testing Issues

### Snapshots Persist Between Tests

**Problem**: Snapshots from one test affect another

**Solutions**:

1. **Use Array Storage in Tests**:
```php
// In TestCase
protected function setUp(): void
{
    parent::setUp();
    Snapshot::setStorage(new ArrayStorage());
}
```

2. **Clear Between Tests**:
```php
protected function tearDown(): void
{
    Snapshot::clear();
    parent::tearDown();
}
```

3. **Use Database Transactions**:
```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SnapshotTest extends TestCase
{
    use DatabaseTransactions;
    // Snapshots will be rolled back with other database changes
}
```

---

## Debugging Tips

### Enable Debug Mode

Add debugging to your snapshot operations:

```php
// Temporary debugging
try {
    $snapshot = Snapshot::save($model, 'debug-test');
    Log::info('Snapshot created', ['snapshot' => $snapshot]);
} catch (Exception $e) {
    Log::error('Snapshot failed', [
        'error' => $e->getMessage(),
        'model' => get_class($model),
        'model_id' => $model->id ?? 'unknown',
        'trace' => $e->getTraceAsString()
    ]);
}
```

### Check Configuration Values

```php
// In tinker - debug configuration
>>> config('snapshot')  # Full config
>>> config('snapshot.default')  # Default driver
>>> config('snapshot.automatic.enabled')  # Automatic snapshots enabled?
```

### Validate Storage

```php
// Test storage directly
$storage = app('Grazulex\LaravelSnapshot\Storage\SnapshotStorage');
$testData = ['test' => 'data'];
$result = $storage->save('test-label', $testData);
$loaded = $storage->load('test-label');
$deleted = $storage->delete('test-label');
```

---

## Getting Help

### Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

### Enable Query Logging

```php
// In a service provider or tinker
DB::listen(function ($query) {
    Log::info('Query', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time
    ]);
});
```

### Report Issues

When reporting issues, include:

1. **Laravel version**: `php artisan --version`
2. **PHP version**: `php -v`
3. **Package version**: `composer show grazulex/laravel-snapshot`
4. **Error message**: Full error with stack trace
5. **Configuration**: Relevant parts of `config/snapshot.php`
6. **Steps to reproduce**: Minimal code example

### Community Support

- **GitHub Issues**: [https://github.com/Grazulex/laravel-snapshot/issues](https://github.com/Grazulex/laravel-snapshot/issues)
- **Discussions**: [https://github.com/Grazulex/laravel-snapshot/discussions](https://github.com/Grazulex/laravel-snapshot/discussions)

---

## Common Solutions Quick Reference

| Problem | Quick Fix |
|---------|-----------|
| Config not working | `php artisan config:clear` |
| Commands not found | `composer dump-autoload` |
| Database errors | Check connection & permissions |
| File permission errors | `chmod 755 storage/snapshots` |
| Memory errors | Exclude relationships/large fields |
| Automatic snapshots not working | Check trait usage & config |
| Tests interfering | Use ArrayStorage in tests |
| Slow performance | Add database indexes |

---

## Prevention Tips

1. **Always test** in development before production
2. **Use array storage** in tests to avoid side effects  
3. **Set up retention policies** to prevent storage bloat
4. **Monitor disk space** when using file storage
5. **Use indexes** on database storage for performance
6. **Exclude sensitive fields** in configuration
7. **Set up proper logging** to catch issues early

## Next Steps

- [Advanced Usage](advanced-usage.md) - Performance optimization and advanced features
- [API Reference](api-reference.md) - Complete method documentation
- [Examples](../examples/README.md) - Working code examples