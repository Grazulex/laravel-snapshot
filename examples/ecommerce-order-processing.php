<?php

declare(strict_types=1);
/**
 * Example: E-commerce Order Processing
 * Description: Demonstrates comprehensive order tracking through processing pipeline
 *
 * Prerequisites:
 * - Order model with HasSnapshots trait
 * - Laravel Snapshot package configured
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/ecommerce-order-processing.php';
 */

use Grazulex\LaravelSnapshot\Snapshot;
use Grazulex\LaravelSnapshot\Traits\HasSnapshots;
use Illuminate\Database\Eloquent\Model;

echo "=== E-commerce Order Processing with Snapshots ===\n\n";

// Simulate Order model
class EcommerceOrder extends Model
{
    use HasSnapshots;

    public $timestamps = false;

    protected $fillable = [
        'order_number', 'customer_id', 'customer_name', 'customer_email',
        'subtotal', 'tax_amount', 'discount_amount', 'shipping_amount', 'total',
        'status', 'payment_status', 'shipping_address', 'items',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'items' => 'array',
    ];
}

class OrderProcessor
{
    public function processOrder(EcommerceOrder $order): bool
    {
        echo "Processing Order #{$order->order_number}\n";
        echo "Customer: {$order->customer_name} ({$order->customer_email})\n";
        echo "Initial Total: \${$order->total}\n\n";

        try {
            // Step 1: Initial snapshot
            Snapshot::save($order, "order-{$order->order_number}-received");
            echo "✓ Created snapshot: order-received\n";

            // Step 2: Validate order
            $this->validateOrder($order);
            $order->status = 'validated';
            Snapshot::save($order, "order-{$order->order_number}-validated");
            echo "✓ Order validated and snapshot created\n";

            // Step 3: Apply discounts
            $this->applyDiscounts($order);
            Snapshot::save($order, "order-{$order->order_number}-discounted");
            echo "✓ Discounts applied: \${$order->discount_amount} saved\n";

            // Step 4: Calculate taxes
            $this->calculateTaxes($order);
            Snapshot::save($order, "order-{$order->order_number}-taxed");
            echo "✓ Taxes calculated: \${$order->tax_amount}\n";

            // Step 5: Calculate shipping
            $this->calculateShipping($order);
            Snapshot::save($order, "order-{$order->order_number}-shipping-calculated");
            echo "✓ Shipping calculated: \${$order->shipping_amount}\n";

            // Step 6: Finalize total
            $this->finalizeTotals($order);
            $order->status = 'ready_for_payment';
            Snapshot::save($order, "order-{$order->order_number}-ready-payment");
            echo "✓ Final total: \${$order->total}\n";

            // Step 7: Process payment
            $this->processPayment($order);
            $order->payment_status = 'paid';
            $order->status = 'paid';
            Snapshot::save($order, "order-{$order->order_number}-paid");
            echo "✓ Payment processed successfully\n";

            // Step 8: Prepare for fulfillment
            $order->status = 'fulfillment_ready';
            Snapshot::save($order, "order-{$order->order_number}-fulfillment-ready");
            echo "✓ Order ready for fulfillment\n";

            return true;

        } catch (Exception $e) {
            // On error, create error snapshot and analyze what went wrong
            $this->handleProcessingError($order, $e);

            return false;
        }
    }

    public function generateProcessingReport(EcommerceOrder $order): array
    {
        echo "\n=== Order Processing Report ===\n";

        // Get all snapshots for this order
        $snapshots = Snapshot::list();
        $orderSnapshots = [];

        foreach ($snapshots as $label => $snapshot) {
            if (mb_strpos($label, "order-{$order->order_number}-") === 0) {
                $orderSnapshots[$label] = $snapshot;
            }
        }

        echo "Processing steps completed:\n";
        foreach ($orderSnapshots as $label => $snapshot) {
            $step = str_replace("order-{$order->order_number}-", '', $label);
            $timestamp = $snapshot['timestamp'] ?? 'N/A';
            echo "  ✓ {$step}: {$timestamp}\n";
        }

        // Calculate processing metrics
        if (count($orderSnapshots) >= 2) {
            $firstStep = array_first($orderSnapshots);
            $lastStep = array_last($orderSnapshots);

            // In a real implementation, you'd calculate actual time differences
            echo "\nProcessing Summary:\n";
            echo '  - Total Steps: '.count($orderSnapshots)."\n";
            echo '  - Start Time: '.($firstStep['timestamp'] ?? 'N/A')."\n";
            echo '  - End Time: '.($lastStep['timestamp'] ?? 'N/A')."\n";

            // Show financial changes
            $firstData = $firstStep['attributes'] ?? [];
            $lastData = $lastStep['attributes'] ?? [];

            if (isset($firstData['total']) && isset($lastData['total'])) {
                $totalChange = $lastData['total'] - $firstData['total'];
                echo "  - Total Change: \${$totalChange}\n";
            }
        }

        return $orderSnapshots;
    }

    private function validateOrder(EcommerceOrder $order): void
    {
        // Simulate validation logic
        if (empty($order->items) || count($order->items) === 0) {
            throw new Exception('Order has no items');
        }

        if ($order->total <= 0) {
            throw new Exception('Order total must be greater than zero');
        }
    }

    private function applyDiscounts(EcommerceOrder $order): void
    {
        // Simulate discount calculation
        $discountRate = 0.10; // 10% discount
        $order->discount_amount = round($order->subtotal * $discountRate, 2);
    }

    private function calculateTaxes(EcommerceOrder $order): void
    {
        // Simulate tax calculation
        $taxRate = 0.08; // 8% tax
        $taxableAmount = $order->subtotal - $order->discount_amount;
        $order->tax_amount = round($taxableAmount * $taxRate, 2);
    }

    private function calculateShipping(EcommerceOrder $order): void
    {
        // Simulate shipping calculation based on location and weight
        $baseShipping = 5.99;
        $weightFactor = count($order->items) * 1.50;
        $order->shipping_amount = round($baseShipping + $weightFactor, 2);
    }

    private function finalizeTotals(EcommerceOrder $order): void
    {
        $order->total = $order->subtotal - $order->discount_amount + $order->tax_amount + $order->shipping_amount;
        $order->total = round($order->total, 2);
    }

    private function processPayment(EcommerceOrder $order): void
    {
        // Simulate payment processing
        if ($order->total > 1000) {
            throw new Exception('Payment declined: Amount too high for demo');
        }

        // Simulate some processing time
        usleep(100000); // 0.1 second
    }

    private function handleProcessingError(EcommerceOrder $order, Exception $e): void
    {
        echo "\n❌ Error during processing: {$e->getMessage()}\n";

        // Create error snapshot
        $order->status = 'error';
        $order->error_message = $e->getMessage();
        Snapshot::save($order, "order-{$order->order_number}-error");

        // Analyze what changed during processing
        $this->analyzeProcessingErrors($order);
    }

    private function analyzeProcessingErrors(EcommerceOrder $order): void
    {
        echo "\nAnalyzing processing errors:\n";

        try {
            // Compare current state with initial state
            $initialLabel = "order-{$order->order_number}-received";
            $errorLabel = "order-{$order->order_number}-error";

            $diff = Snapshot::diff($initialLabel, $errorLabel);

            if (isset($diff['modified'])) {
                echo "Fields modified during processing:\n";
                foreach ($diff['modified'] as $field => $changes) {
                    echo "  - {$field}: {$changes['from']} → {$changes['to']}\n";
                }
            }

        } catch (Exception $e) {
            echo "Could not analyze processing errors: {$e->getMessage()}\n";
        }
    }
}

// Run the example
try {
    // Create sample order
    $order = new EcommerceOrder();
    $order->fill([
        'order_number' => 'ORD-2024-001',
        'customer_id' => 1,
        'customer_name' => 'Alice Johnson',
        'customer_email' => 'alice@example.com',
        'subtotal' => 150.00,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'shipping_amount' => 0,
        'total' => 150.00,
        'status' => 'received',
        'payment_status' => 'pending',
        'shipping_address' => [
            'street' => '123 Main St',
            'city' => 'Portland',
            'state' => 'OR',
            'zip' => '97201',
        ],
        'items' => [
            ['name' => 'Widget A', 'price' => 50.00, 'quantity' => 2],
            ['name' => 'Widget B', 'price' => 25.00, 'quantity' => 2],
        ],
    ]);

    $order->exists = true;
    $order->id = 1;

    // Process the order
    $processor = new OrderProcessor();
    $success = $processor->processOrder($order);

    if ($success) {
        echo "\n🎉 Order processed successfully!\n";

        // Generate comprehensive report
        $snapshots = $processor->generateProcessingReport($order);

        // Show final comparison
        echo "\n=== Before vs After Comparison ===\n";
        $diff = Snapshot::diff(
            "order-{$order->order_number}-received",
            "order-{$order->order_number}-fulfillment-ready"
        );

        if (isset($diff['modified'])) {
            foreach ($diff['modified'] as $field => $changes) {
                echo "  {$field}: {$changes['from']} → {$changes['to']}\n";
            }
        }
    }

} catch (Exception $e) {
    echo "Example error: {$e->getMessage()}\n";
}

// Cleanup
echo "\nCleaning up snapshots...\n";
$cleanedUp = Snapshot::clear();
echo "Deleted {$cleanedUp} snapshots.\n";

echo "\nExample completed!\n";
echo "\nKey Takeaways:\n";
echo "- Snapshots provide complete audit trail of order processing\n";
echo "- Easy to identify where errors occurred in the pipeline\n";
echo "- Can compare any two points in processing\n";
echo "- Valuable for debugging and compliance\n";
