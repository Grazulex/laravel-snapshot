<?php

declare(strict_types=1);
/**
 * Example: Automated Testing
 * Description: Using snapshots in feature tests for reliable state verification
 *
 * Prerequisites:
 * - Laravel testing environment
 * - Laravel Snapshot package configured
 * - Models for testing (User, Order, etc.)
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/automated-testing.php';
 */

use App\Models\User;
use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Storage\ArrayStorage;

echo "=== Automated Testing Example ===\n\n";

// Setup test environment
echo "1. Setting up test environment:\n";

// Use in-memory storage for tests (won't persist between test runs)
$testStorage = new ArrayStorage();
Snapshot::setStorage($testStorage);
echo "   ✓ Test storage (array-based) configured\n";

// Test helper functions
function assertSnapshotEquals(string $label, $expectedData, string $message = ''): bool
{
    $snapshot = Snapshot::load($label);
    if (!$snapshot) {
        echo "   ✗ FAIL: Snapshot '{$label}' not found\n";
        return false;
    }
    
    $actualData = $snapshot['data'] ?? $snapshot;
    
    // Simple equality check (in real tests, you'd use proper assertions)
    if (json_encode($actualData) === json_encode($expectedData)) {
        echo "   ✓ PASS: {$message}\n";
        return true;
    } else {
        echo "   ✗ FAIL: {$message}\n";
        echo "     Expected: " . json_encode($expectedData) . "\n";
        echo "     Actual: " . json_encode($actualData) . "\n";
        return false;
    }
}

function assertSnapshotDifference(string $labelA, string $labelB, array $expectedChanges, string $message = ''): bool
{
    $diff = Snapshot::diff($labelA, $labelB);
    
    // Check if expected changes exist
    $passed = true;
    foreach ($expectedChanges as $type => $expectedFields) {
        if (!isset($diff[$type])) {
            echo "   ✗ FAIL: {$message} - Missing {$type} changes\n";
            $passed = false;
            continue;
        }
        
        foreach ($expectedFields as $field => $expectedValue) {
            if (!isset($diff[$type][$field])) {
                echo "   ✗ FAIL: {$message} - Missing {$type} change for field '{$field}'\n";
                $passed = false;
            }
        }
    }
    
    if ($passed) {
        echo "   ✓ PASS: {$message}\n";
    }
    
    return $passed;
}

// Test Case 1: User Registration Flow
echo "\n2. Test Case: User Registration Flow\n";

// Mock user data
$newUser = [
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'email_verified_at' => null,
    'status' => 'pending',
];

// Snapshot initial state
Snapshot::save($newUser, 'test-user-registration-start');
echo "   ✓ Initial user state captured\n";

// Simulate email verification
$newUser['email_verified_at'] = '2024-07-19 10:00:00';
$newUser['status'] = 'active';
Snapshot::save($newUser, 'test-user-after-verification');
echo "   ✓ User state after email verification captured\n";

// Test assertions
assertSnapshotEquals('test-user-registration-start', [
    'id' => 1,
    'name' => 'John Doe', 
    'email' => 'john@example.com',
    'email_verified_at' => null,
    'status' => 'pending',
], 'Initial user state matches expected values');

assertSnapshotDifference(
    'test-user-registration-start',
    'test-user-after-verification',
    [
        'modified' => [
            'email_verified_at' => '2024-07-19 10:00:00',
            'status' => 'active',
        ]
    ],
    'Email verification changes are correct'
);

// Test Case 2: Order Processing Pipeline
echo "\n3. Test Case: Order Processing Pipeline\n";

// Mock order processing states
$order = [
    'id' => 100,
    'user_id' => 1,
    'status' => 'pending',
    'items' => [
        ['product_id' => 1, 'quantity' => 2, 'price' => 29.99],
        ['product_id' => 2, 'quantity' => 1, 'price' => 49.99],
    ],
    'total' => 109.97,
    'payment_status' => null,
    'shipped_at' => null,
];

// Capture each stage of order processing
Snapshot::save($order, 'test-order-created');
echo "   ✓ Order created state captured\n";

// Payment processing
$order['payment_status'] = 'paid';
$order['status'] = 'processing';
Snapshot::save($order, 'test-order-paid');
echo "   ✓ Order payment state captured\n";

// Shipping
$order['status'] = 'shipped';
$order['shipped_at'] = '2024-07-19 12:00:00';
Snapshot::save($order, 'test-order-shipped');
echo "   ✓ Order shipped state captured\n";

// Delivery
$order['status'] = 'delivered';
Snapshot::save($order, 'test-order-delivered');
echo "   ✓ Order delivered state captured\n";

// Test the complete workflow
assertSnapshotDifference(
    'test-order-created',
    'test-order-paid',
    [
        'modified' => [
            'payment_status' => 'paid',
            'status' => 'processing',
        ]
    ],
    'Payment processing changes are correct'
);

assertSnapshotDifference(
    'test-order-paid',
    'test-order-shipped',
    [
        'modified' => [
            'status' => 'shipped',
            'shipped_at' => '2024-07-19 12:00:00',
        ]
    ],
    'Shipping changes are correct'
);

// Test Case 3: Feature Flag Testing
echo "\n4. Test Case: Feature Flag Impact Testing\n";

// Mock application configuration
$config = [
    'features' => [
        'new_checkout' => false,
        'advanced_search' => false,
        'email_notifications' => true,
    ],
    'performance' => [
        'cache_ttl' => 300,
        'max_connections' => 100,
    ],
];

Snapshot::save($config, 'test-config-original');
echo "   ✓ Original configuration captured\n";

// Enable new feature
$config['features']['new_checkout'] = true;
$config['performance']['cache_ttl'] = 600; // Increase cache for new feature
Snapshot::save($config, 'test-config-with-new-checkout');
echo "   ✓ Configuration with new checkout feature captured\n";

// Test feature impact
assertSnapshotDifference(
    'test-config-original',
    'test-config-with-new-checkout',
    [
        'modified' => [
            'features' => ['new_checkout' => true],
            'performance' => ['cache_ttl' => 600],
        ]
    ],
    'Feature flag changes have expected impact'
);

// Test Case 4: Data Migration Testing
echo "\n5. Test Case: Data Migration Verification\n";

// Mock database records before migration
$usersBeforeMigration = [
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
];

Snapshot::save($usersBeforeMigration, 'test-users-before-migration');
echo "   ✓ Users before migration captured\n";

// Simulate migration adding new fields
$usersAfterMigration = [
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'profile_completed' => false, 'last_login' => null],
    ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'profile_completed' => false, 'last_login' => null],
];

Snapshot::save($usersAfterMigration, 'test-users-after-migration');
echo "   ✓ Users after migration captured\n";

// Verify migration results
$migrationDiff = Snapshot::diff('test-users-before-migration', 'test-users-after-migration');
if (isset($migrationDiff['modified']) || isset($migrationDiff['added'])) {
    echo "   ✓ PASS: Migration added expected fields\n";
    
    // In real tests, you'd verify specific field additions
    foreach ($usersAfterMigration as $user) {
        if (isset($user['profile_completed']) && isset($user['last_login'])) {
            echo "     - User {$user['id']}: New fields added correctly\n";
        }
    }
} else {
    echo "   ✗ FAIL: Migration did not add expected fields\n";
}

// Test Case 5: Performance Regression Testing
echo "\n6. Test Case: Performance Regression Testing\n";

// Mock performance metrics
$performanceBefore = [
    'response_time_ms' => 250,
    'memory_usage_mb' => 45,
    'database_queries' => 12,
    'cache_hit_rate' => 0.85,
];

Snapshot::save($performanceBefore, 'test-performance-baseline');
echo "   ✓ Performance baseline captured\n";

// Simulate code changes affecting performance
$performanceAfter = [
    'response_time_ms' => 280,
    'memory_usage_mb' => 52,
    'database_queries' => 15,
    'cache_hit_rate' => 0.82,
];

Snapshot::save($performanceAfter, 'test-performance-after-changes');
echo "   ✓ Performance after changes captured\n";

// Performance regression detection
$performanceDiff = Snapshot::diff('test-performance-baseline', 'test-performance-after-changes');
if (isset($performanceDiff['modified'])) {
    echo "   Performance changes detected:\n";
    foreach ($performanceDiff['modified'] as $metric => $change) {
        $from = $change['from'];
        $to = $change['to'];
        $degraded = '';
        
        // Detect regressions
        if ($metric === 'response_time_ms' && $to > $from) $degraded = ' (REGRESSION)';
        if ($metric === 'memory_usage_mb' && $to > $from) $degraded = ' (REGRESSION)';
        if ($metric === 'database_queries' && $to > $from) $degraded = ' (REGRESSION)';
        if ($metric === 'cache_hit_rate' && $to < $from) $degraded = ' (REGRESSION)';
        
        echo "     - {$metric}: {$from} → {$to}{$degraded}\n";
    }
}

// Test Case 6: API Response Testing
echo "\n7. Test Case: API Response Consistency Testing\n";

// Mock API responses
$apiResponseV1 = [
    'status' => 'success',
    'data' => [
        'user' => ['id' => 1, 'name' => 'John Doe'],
        'preferences' => ['theme' => 'light', 'language' => 'en'],
    ],
    'meta' => [
        'version' => '1.0',
        'timestamp' => '2024-07-19T10:00:00Z',
    ],
];

Snapshot::save($apiResponseV1, 'test-api-response-v1');
echo "   ✓ API v1 response structure captured\n";

// API version update
$apiResponseV2 = [
    'status' => 'success',
    'data' => [
        'user' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'], // Added email
        'preferences' => ['theme' => 'light', 'language' => 'en', 'notifications' => true], // Added notifications
    ],
    'meta' => [
        'version' => '2.0', // Version updated
        'timestamp' => '2024-07-19T10:00:00Z',
    ],
];

Snapshot::save($apiResponseV2, 'test-api-response-v2');
echo "   ✓ API v2 response structure captured\n";

// Verify backward compatibility
$apiDiff = Snapshot::diff('test-api-response-v1', 'test-api-response-v2');
if (isset($apiDiff['added'])) {
    echo "   ✓ PASS: API v2 adds fields without breaking existing structure\n";
} else if (isset($apiDiff['removed'])) {
    echo "   ✗ FAIL: API v2 removes fields (breaking change)\n";
} else {
    echo "   ✓ PASS: API structure unchanged\n";
}

// Test Framework Integration Examples
echo "\n8. Test Framework Integration Examples:\n";

echo "   PHPUnit Integration:\n";
$phpunitExample = <<<'PHP'
public function test_user_registration_workflow()
{
    // Arrange
    $user = User::factory()->make();
    Snapshot::save($user, 'user-before-save');
    
    // Act
    $user->save();
    Snapshot::save($user, 'user-after-save');
    
    // Assert
    $diff = Snapshot::diff('user-before-save', 'user-after-save');
    $this->assertArrayHasKey('modified', $diff);
    $this->assertArrayHasKey('id', $diff['modified']);
}

PHP;
echo $phpunitExample;

echo "\n   Pest Integration:\n";
$pestExample = <<<'PHP'
it('processes orders correctly', function () {
    $order = Order::factory()->create(['status' => 'pending']);
    
    Snapshot::save($order, 'order-pending');
    
    $order->process();
    
    Snapshot::save($order, 'order-processed');
    
    $diff = Snapshot::diff('order-pending', 'order-processed');
    
    expect($diff['modified']['status']['to'])->toBe('processed');
});

PHP;
echo $pestExample;

// Test Statistics
echo "\n9. Test Run Statistics:\n";
$allSnapshots = Snapshot::list();
$testSnapshots = array_filter($allSnapshots, function($snapshot, $label) {
    return strpos($label, 'test-') === 0;
}, ARRAY_FILTER_USE_BOTH);

echo "   - Total test snapshots created: " . count($testSnapshots) . "\n";
echo "   - Test cases executed: 6\n";
echo "   - Assertions performed: Multiple per test case\n";

// Best Practices Summary
echo "\n10. Testing Best Practices with Snapshots:\n";
echo "   ✓ Use ArrayStorage for test isolation\n";
echo "   ✓ Create snapshots before and after operations\n";
echo "   ✓ Test state transitions with diff comparison\n";
echo "   ✓ Verify expected changes and catch regressions\n";
echo "   ✓ Use descriptive snapshot labels\n";
echo "   ✓ Clean up test snapshots after test runs\n";
echo "   ✓ Test both positive and negative scenarios\n";
echo "   ✓ Capture performance metrics for regression testing\n";

// Cleanup
echo "\n11. Test cleanup:\n";
$clearedCount = Snapshot::clear();
echo "   ✓ Cleaned up {$clearedCount} test snapshots\n";

echo "\n=== Automated Testing Benefits Demonstrated ===\n";
echo "✓ State verification in test scenarios\n";
echo "✓ Regression detection with before/after comparison\n";
echo "✓ API response consistency testing\n";
echo "✓ Data migration verification\n";
echo "✓ Performance regression detection\n";
echo "✓ Feature flag impact testing\n";
echo "✓ Test isolation with in-memory storage\n";
echo "✓ Integration with PHPUnit and Pest\n";

echo "\nAutomated testing example completed successfully!\n";