# Laravel Snapshot Examples

This directory contains practical examples demonstrating various Laravel Snapshot features and use cases.

## Quick Start Examples

1. [Basic Usage](basic-usage.php) - Simple snapshot creation and comparison
2. [Model with Trait](model-with-trait.php) - Using the HasSnapshots trait
3. [Console Commands](console-commands.md) - Command-line examples

## Real-World Use Cases

4. [E-commerce Order Processing](ecommerce-order-processing.php) - Track order changes through processing pipeline
5. [User Profile Auditing](user-profile-auditing.php) - Audit trail for user profile changes
6. [Content Management](content-management.php) - Track changes to articles and pages
7. [Financial Transaction Tracking](financial-transaction-tracking.php) - Monitor critical financial data

## Advanced Features

8. [Custom Storage Backend](custom-storage-backend.php) - Implement custom storage driver
9. [Automated Testing](automated-testing.php) - Using snapshots in feature tests
10. [Scheduled Snapshots](scheduled-snapshots.php) - Periodic automatic snapshots
11. [Report Generation](report-generation.php) - Generate comprehensive reports

## Configuration Examples

12. [Production Configuration](config-production.php) - Production-ready configuration
13. [Development Configuration](config-development.php) - Development environment setup
14. [Multi-tenant Setup](config-multi-tenant.php) - Configuration for multi-tenant applications

## Integration Examples

15. [API Integration](api-integration.php) - Snapshot API endpoints for external integrations
16. [Event Sourcing](event-sourcing.php) - Using snapshots for event sourcing patterns
17. [Data Migration](data-migration.php) - Using snapshots during data migrations

## Performance Examples

18. [High Volume Applications](high-volume-optimization.php) - Optimization for high-traffic applications
19. [Large Model Handling](large-model-handling.php) - Handling models with many attributes/relationships

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