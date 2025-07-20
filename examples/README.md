# Laravel Snapshot Examples

This directory contains practical examples demonstrating various Laravel Snapshot features and use cases.

## Available Examples

### Core Examples
1. [Basic Usage](basic-usage.php) - Simple snapshot creation and comparison
2. [Model with Trait](model-with-trait.php) - Using the HasSnapshots trait  
3. [Console Commands](console-commands.md) - Command-line interface examples

### Real-World Use Cases
4. [E-commerce Order Processing](ecommerce-order-processing.php) - Comprehensive order tracking through processing pipeline
5. [User Profile Auditing](user-profile-auditing.php) - Comprehensive audit trail for user profile changes

### Advanced Features
6. [Scheduled Snapshots](scheduled-snapshots.php) - Periodic snapshots with cron integration and monitoring
7. [Automated Testing](automated-testing.php) - Using snapshots in feature tests and test-driven development

### Coming Soon
Additional examples are being developed and will be added in future releases:
- Content Management - Track content versions  
- Financial Transaction Tracking - Monitor financial data
- Custom Storage Backend - Implement custom drivers
- Report Generation - Advanced reporting examples
- API Integration - External system integration

## Running Examples

All PHP examples can be run in several ways:

### Method 1: Tinker (Recommended)

```bash
php artisan tinker
>>> include 'examples/basic-usage.php';
```

### Method 2: Artisan Command

Create a custom command to run examples:

```bash
php artisan make:command RunExample
```

### Method 3: Test Environment

Run examples in a test case to ensure they work correctly.

## Requirements

- Laravel Snapshot package installed and configured
- Sample data (User, Order, etc. models)
- Configured database connection

## Contributing Examples

Have a useful example? Please contribute by:

1. Creating a new PHP file with descriptive comments
2. Adding it to this README
3. Including setup instructions if needed
4. Testing the example works

## Example Template

Use this template for new examples:

```php
<?php
/**
 * Example: [Title]
 * Description: [Brief description of what this example demonstrates]
 * 
 * Prerequisites:
 * - [List any required models, data, or configuration]
 * 
 * Usage:
 * php artisan tinker
 * >>> include 'examples/your-example.php';
 */

use Grazulex\LaravelSnapshot\Snapshot;

// Your example code here...

echo "Example completed successfully!\n";
```

## Support

If you have issues with any examples:

1. Check the [troubleshooting guide](../docs/troubleshooting.md)
2. Ensure your configuration is correct
3. Verify your database connection and migrations
4. Check Laravel logs for detailed error messages

## Next Steps

After exploring these examples:

- Read the [API documentation](../docs/api-reference.md)
- Learn about [advanced usage](../docs/advanced-usage.md)
- Explore [configuration options](../docs/configuration.md)