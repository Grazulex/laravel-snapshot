<?php

declare(strict_types=1);
/**
 * Example: Automated Testing with Snapshots
 * Description: Demonstrates using snapshots in automated tests for verification and debugging
 *
 * Prerequisites:
 * - Laravel Snapshot package configured
 * - PHPUnit/Pest test framework
 * - Test models and factories
 *
 * Usage:
 * This file contains test examples. In actual usage, place these in your test files.
 * Run tests with: php artisan test
 */

use App\Models\User;
use App\Models\Order;
use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;
use Tests\TestCase;

echo "=== Automated Testing with Snapshots Example ===\n\n";

/**
 * Example Test Case: Testing User Profile Updates
 */
class UserProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use in-memory storage for tests (faster and isolated)
        Snapshot::setStorage(new ArrayStorage());
    }
    
    public function test_user_profile_update_changes_expected_fields()
    {
        // Arrange: Create a user
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active'
        ]);
        
        // Create baseline snapshot
        Snapshot::save($user, 'baseline');
        
        // Act: Update user profile
        $user->update([
            'name' => 'John H. Doe',
            'email' => 'john.h.doe@example.com'
        ]);
        
        // Create post-update snapshot
        Snapshot::save($user, 'after-update');
        
        // Assert: Verify expected changes
        $diff = Snapshot::diff('baseline', 'after-update');
        
        $this->assertArrayHasKey('modified', $diff);
        $this->assertArrayHasKey('name', $diff['modified']);
        $this->assertArrayHasKey('email', $diff['modified']);
        
        // Verify specific change values
        $this->assertEquals('John Doe', $diff['modified']['name']['from']);
        $this->assertEquals('John H. Doe', $diff['modified']['name']['to']);
        
        // Verify no unexpected changes
        $this->assertArrayNotHasKey('status', $diff['modified']);
        $this->assertArrayNotHasKey('added', $diff);
        $this->assertArrayNotHasKey('removed', $diff);
    }
    
    public function test_user_deletion_is_tracked()
    {
        $user = User::factory()->create();
        
        // Snapshot before deletion
        Snapshot::save($user, 'before-deletion');
        
        // Get user data before deletion
        $originalData = $user->toArray();
        
        // Delete user
        $userId = $user->id;
        $user->delete();
        
        // Verify snapshot can be loaded after deletion
        $snapshot = Snapshot::load('before-deletion');
        
        $this->assertNotNull($snapshot);
        $this->assertEquals($originalData['name'], $snapshot['attributes']['name']);
        $this->assertEquals($originalData['email'], $snapshot['attributes']['email']);
    }
}

/**
 * Example Test Case: Order Processing Workflow
 */
class OrderProcessingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot::setStorage(new ArrayStorage());
    }
    
    public function test_order_processing_workflow_changes_status_correctly()
    {
        // Create order
        $order = Order::factory()->create([
            'status' => 'pending',
            'total' => 100.00,
            'payment_status' => 'unpaid'
        ]);
        
        // Snapshot each stage of processing
        Snapshot::save($order, 'created');
        
        // Process payment
        $order->update(['payment_status' => 'paid']);
        Snapshot::save($order, 'payment-processed');
        
        // Ship order  
        $order->update(['status' => 'shipped']);
        Snapshot::save($order, 'shipped');
        
        // Complete order
        $order->update(['status' => 'completed']);
        Snapshot::save($order, 'completed');
        
        // Verify each transition
        $paymentDiff = Snapshot::diff('created', 'payment-processed');
        $this->assertEquals('paid', $paymentDiff['modified']['payment_status']['to']);
        
        $shipDiff = Snapshot::diff('payment-processed', 'shipped');
        $this->assertEquals('shipped', $shipDiff['modified']['status']['to']);
        
        $completeDiff = Snapshot::diff('shipped', 'completed');
        $this->assertEquals('completed', $completeDiff['modified']['status']['to']);
        
        // Verify full workflow
        $fullDiff = Snapshot::diff('created', 'completed');
        $this->assertEquals('pending', $fullDiff['modified']['status']['from']);
        $this->assertEquals('completed', $fullDiff['modified']['status']['to']);
        $this->assertEquals('unpaid', $fullDiff['modified']['payment_status']['from']);
        $this->assertEquals('paid', $fullDiff['modified']['payment_status']['to']);
    }
    
    public function test_failed_order_processing_leaves_no_partial_changes()
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'payment_status' => 'unpaid'
        ]);
        
        Snapshot::save($order, 'before-processing');
        
        try {
            // Simulate processing that might fail
            $order->update(['payment_status' => 'processing']);
            
            // Simulate failure - throw exception
            throw new \Exception('Payment gateway error');
            
            // This should not be reached
            $order->update(['payment_status' => 'paid']);
            
        } catch (\Exception $e) {
            // Rollback to previous state
            $order->update(['payment_status' => 'unpaid']);
        }
        
        Snapshot::save($order, 'after-failed-processing');
        
        // Verify no net changes after failed processing
        $diff = Snapshot::diff('before-processing', 'after-failed-processing');
        $this->assertEmpty($diff['modified'] ?? []);
    }
}

/**
 * Example Test Case: Data Migration Testing
 */
class DataMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot::setStorage(new ArrayStorage());
    }
    
    public function test_data_migration_preserves_critical_fields()
    {
        // Create users with old data structure
        $users = User::factory()->count(5)->create();
        
        // Snapshot all users before migration
        foreach ($users as $user) {
            Snapshot::save($user, "pre-migration-user-{$user->id}");
        }
        
        // Simulate migration (e.g., adding full_name field from first_name + last_name)
        foreach ($users as $user) {
            $user->update([
                'full_name' => $user->first_name . ' ' . $user->last_name
            ]);
            
            Snapshot::save($user, "post-migration-user-{$user->id}");
        }
        
        // Verify migration for each user
        foreach ($users as $user) {
            $diff = Snapshot::diff(
                "pre-migration-user-{$user->id}",
                "post-migration-user-{$user->id}"
            );
            
            // Should have added full_name field
            $this->assertArrayHasKey('added', $diff);
            $this->assertArrayHasKey('full_name', $diff['added']);
            
            // Should not have modified critical fields
            $criticalFields = ['id', 'email', 'created_at'];
            foreach ($criticalFields as $field) {
                $this->assertArrayNotHasKey($field, $diff['modified'] ?? []);
            }
        }
    }
}

/**
 * Example Test Case: Performance Testing with Snapshots
 */
class PerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Snapshot::setStorage(new ArrayStorage());
    }
    
    public function test_bulk_operation_performance()
    {
        $users = User::factory()->count(100)->create();
        
        // Snapshot before bulk operation
        $startTime = microtime(true);
        foreach ($users as $user) {
            Snapshot::save($user, "before-bulk-{$user->id}");
        }
        $snapshotTime = microtime(true) - $startTime;
        
        // Perform bulk operation
        $startTime = microtime(true);
        User::whereIn('id', $users->pluck('id'))->update(['status' => 'bulk-updated']);
        $updateTime = microtime(true) - $startTime;
        
        // Snapshot after bulk operation  
        $users = $users->fresh();
        $startTime = microtime(true);
        foreach ($users as $user) {
            Snapshot::save($user, "after-bulk-{$user->id}");
        }
        $postSnapshotTime = microtime(true) - $startTime;
        
        // Performance assertions
        $this->assertLessThan(5.0, $snapshotTime, 'Snapshot creation should be under 5 seconds for 100 models');
        $this->assertLessThan(1.0, $updateTime, 'Bulk update should be under 1 second');
        $this->assertLessThan(5.0, $postSnapshotTime, 'Post-update snapshots should be under 5 seconds');
        
        // Verify changes were applied
        foreach ($users as $user) {
            $diff = Snapshot::diff("before-bulk-{$user->id}", "after-bulk-{$user->id}");
            $this->assertEquals('bulk-updated', $diff['modified']['status']['to']);
        }
    }
}

echo "Example Test Classes Created:\n\n";

echo "1. UserProfileTest - Testing profile updates and deletions\n";
echo "   - test_user_profile_update_changes_expected_fields()\n";
echo "   - test_user_deletion_is_tracked()\n\n";

echo "2. OrderProcessingTest - Testing complex workflows\n";
echo "   - test_order_processing_workflow_changes_status_correctly()\n";
echo "   - test_failed_order_processing_leaves_no_partial_changes()\n\n";

echo "3. DataMigrationTest - Testing data migrations\n";
echo "   - test_data_migration_preserves_critical_fields()\n\n";

echo "4. PerformanceTest - Testing performance with snapshots\n";
echo "   - test_bulk_operation_performance()\n\n";

// Test Helper Class Example
class SnapshotTestHelper
{
    public static function createOrderProcessingScenario(): array
    {
        $order = Order::factory()->create(['status' => 'pending']);
        
        // Create snapshots for each processing stage
        Snapshot::save($order, 'order-created');
        
        $order->update(['status' => 'processing']);
        Snapshot::save($order, 'order-processing');
        
        $order->update(['status' => 'shipped']);  
        Snapshot::save($order, 'order-shipped');
        
        $order->update(['status' => 'completed']);
        Snapshot::save($order, 'order-completed');
        
        return [
            'order' => $order,
            'snapshots' => ['order-created', 'order-processing', 'order-shipped', 'order-completed']
        ];
    }
    
    public static function verifyNoDataLoss(string $beforeLabel, string $afterLabel, array $criticalFields): void
    {
        $diff = Snapshot::diff($beforeLabel, $afterLabel);
        
        foreach ($criticalFields as $field) {
            if (isset($diff['removed'][$field])) {
                throw new \Exception("Critical field {$field} was removed");
            }
        }
    }
    
    public static function createSnapshotWithMetadata($model, string $label, array $metadata = []): array
    {
        $snapshot = Snapshot::save($model, $label);
        
        // In a real implementation, you might extend this to store custom metadata
        return array_merge($snapshot, ['test_metadata' => $metadata]);
    }
}

echo "Test Helper Class Created:\n";
echo "- SnapshotTestHelper::createOrderProcessingScenario()\n";
echo "- SnapshotTestHelper::verifyNoDataLoss()\n";
echo "- SnapshotTestHelper::createSnapshotWithMetadata()\n\n";

echo "Best Practices for Testing with Snapshots:\n\n";
echo "1. Always use ArrayStorage for tests (fast, isolated)\n";
echo "2. Create snapshots before and after operations\n";
echo "3. Use meaningful snapshot labels for debugging\n";
echo "4. Test both successful and failed scenarios\n";
echo "5. Verify no unexpected changes occur\n";
echo "6. Use snapshots to test data migrations\n";
echo "7. Create helper methods for common scenarios\n";
echo "8. Test performance with large datasets\n";
echo "9. Verify critical fields are never lost\n";
echo "10. Clean up snapshots between tests (ArrayStorage does this automatically)\n\n";

echo "=== Automated Testing with Snapshots Example Complete ===\n";