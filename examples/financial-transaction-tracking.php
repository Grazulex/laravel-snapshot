<?php

declare(strict_types=1);
/**
 * Example: Financial Transaction Tracking
 * Description: Monitor critical financial data with audit trails
 *
 * Prerequisites:
 * - Laravel Snapshot package configured
 * - Models for financial entities (or we'll mock them)
 *
 * Usage:
 * php artisan tinker
 * >>> include 'examples/financial-transaction-tracking.php';
 */

use Grazulex\LaravelSnapshot\Snapshot;

// Mock financial models for demonstration
class Account
{
    public $id;
    public $number;
    public $balance;
    public $currency;
    public $status;
    
    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? 1;
        $this->number = $data['number'] ?? 'ACC-001';
        $this->balance = $data['balance'] ?? 0.00;
        $this->currency = $data['currency'] ?? 'USD';
        $this->status = $data['status'] ?? 'active';
    }
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
    
    public function update($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

class Transaction
{
    public $id;
    public $account_id;
    public $type;
    public $amount;
    public $description;
    public $status;
    public $reference;
    
    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? 1;
        $this->account_id = $data['account_id'] ?? 1;
        $this->type = $data['type'] ?? 'credit';
        $this->amount = $data['amount'] ?? 0.00;
        $this->description = $data['description'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->reference = $data['reference'] ?? 'TXN-'.uniqid();
    }
    
    public function toArray()
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'status' => $this->status,
            'reference' => $this->reference,
        ];
    }
    
    public function update($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}

echo "=== Financial Transaction Tracking Example ===\n\n";

// Create test account with initial balance
$account = new Account([
    'id' => 12345,
    'number' => 'ACC-12345',
    'balance' => 10000.00,
    'currency' => 'USD',
    'status' => 'active',
]);

echo "1. Created account: {$account->number} with balance \${$account->balance}\n";

// Create initial account snapshot for audit trail
Snapshot::save($account, 'account-opened');
echo "2. Account opening snapshot created for audit compliance\n";

// Simulate financial transactions
echo "\n3. Processing financial transactions:\n";

// Transaction 1: Deposit
sleep(1);
$deposit = new Transaction([
    'id' => 1001,
    'account_id' => $account->id,
    'type' => 'credit',
    'amount' => 2500.00,
    'description' => 'Salary deposit',
    'status' => 'pending',
]);

Snapshot::save($deposit, 'txn-1001-created');
echo "   ✓ Transaction TXN-1001 created: \${$deposit->amount} deposit (pending)\n";

// Process the deposit
sleep(1);
$deposit->update(['status' => 'processing']);
Snapshot::save($deposit, 'txn-1001-processing');
echo "   ✓ Transaction TXN-1001 status: processing\n";

// Complete deposit and update account
sleep(1);
$deposit->update(['status' => 'completed']);
$account->update(['balance' => $account->balance + $deposit->amount]);

Snapshot::save($deposit, 'txn-1001-completed');
Snapshot::save($account, 'account-after-deposit');
echo "   ✓ Transaction TXN-1001 completed: Account balance now \${$account->balance}\n";

// Transaction 2: Large withdrawal requiring approval
sleep(1);
$withdrawal = new Transaction([
    'id' => 1002,
    'account_id' => $account->id,
    'type' => 'debit',
    'amount' => 5000.00,
    'description' => 'Investment transfer',
    'status' => 'pending_approval',
]);

Snapshot::save($withdrawal, 'txn-1002-created');
echo "   ✓ Transaction TXN-1002 created: \${$withdrawal->amount} withdrawal (pending approval)\n";

// Approval process
sleep(1);
$withdrawal->update(['status' => 'approved']);
Snapshot::save($withdrawal, 'txn-1002-approved');
echo "   ✓ Transaction TXN-1002 approved by compliance\n";

// Process withdrawal
sleep(1);
$withdrawal->update(['status' => 'processing']);
Snapshot::save($withdrawal, 'txn-1002-processing');

sleep(1);
$withdrawal->update(['status' => 'completed']);
$account->update(['balance' => $account->balance - $withdrawal->amount]);

Snapshot::save($withdrawal, 'txn-1002-completed');
Snapshot::save($account, 'account-after-withdrawal');
echo "   ✓ Transaction TXN-1002 completed: Account balance now \${$account->balance}\n";

// Transaction 3: Failed transaction
sleep(1);
$failedTxn = new Transaction([
    'id' => 1003,
    'account_id' => $account->id,
    'type' => 'debit',
    'amount' => 15000.00,
    'description' => 'Large purchase attempt',
    'status' => 'pending',
]);

Snapshot::save($failedTxn, 'txn-1003-created');
echo "   ✓ Transaction TXN-1003 created: \${$failedTxn->amount} purchase attempt\n";

sleep(1);
$failedTxn->update(['status' => 'failed']);
Snapshot::save($failedTxn, 'txn-1003-failed');
echo "   ✓ Transaction TXN-1003 failed: Insufficient funds\n";

// Audit trail analysis
echo "\n4. Financial audit trail analysis:\n";
$allSnapshots = Snapshot::list();

// Filter account snapshots
$accountSnapshots = array_filter($allSnapshots, function($snapshot, $label) {
    return strpos($label, 'account-') === 0;
}, ARRAY_FILTER_USE_BOTH);

echo "   Account balance history:\n";
foreach ($accountSnapshots as $label => $snapshot) {
    $data = $snapshot['data'] ?? $snapshot;
    $balance = $data['balance'] ?? 'N/A';
    $timestamp = $snapshot['timestamp'] ?? 'Unknown';
    echo "     - {$label}: \${$balance} ({$timestamp})\n";
}

// Filter transaction snapshots
$transactionSnapshots = array_filter($allSnapshots, function($snapshot, $label) {
    return strpos($label, 'txn-') === 0;
}, ARRAY_FILTER_USE_BOTH);

echo "\n   Transaction audit trail:\n";
foreach ($transactionSnapshots as $label => $snapshot) {
    $data = $snapshot['data'] ?? $snapshot;
    $amount = $data['amount'] ?? 'N/A';
    $status = $data['status'] ?? 'N/A';
    $type = $data['type'] ?? 'N/A';
    echo "     - {$label}: {$type} \${$amount} ({$status})\n";
}

// Compliance reporting
echo "\n5. Compliance and regulatory reporting:\n";

// Transaction lifecycle analysis
$txn1001Snapshots = array_filter($allSnapshots, function($snapshot, $label) {
    return strpos($label, 'txn-1001') === 0;
}, ARRAY_FILTER_USE_BOTH);

echo "   Transaction TXN-1001 lifecycle:\n";
foreach ($txn1001Snapshots as $label => $snapshot) {
    $data = $snapshot['data'] ?? $snapshot;
    $status = $data['status'] ?? 'N/A';
    $timestamp = $snapshot['timestamp'] ?? 'Unknown';
    echo "     - Status: {$status} at {$timestamp}\n";
}

// Balance integrity check
echo "\n6. Balance integrity verification:\n";
$initialBalance = 10000.00;
$depositAmount = 2500.00;
$withdrawalAmount = 5000.00;
$expectedBalance = $initialBalance + $depositAmount - $withdrawalAmount;

echo "   - Initial balance: \${$initialBalance}\n";
echo "   - Total deposits: \${$depositAmount}\n";
echo "   - Total withdrawals: \${$withdrawalAmount}\n";
echo "   - Expected balance: \${$expectedBalance}\n";
echo "   - Current balance: \${$account->balance}\n";
echo "   - Balance verification: ".($account->balance == $expectedBalance ? "✓ PASSED" : "✗ FAILED")."\n";

// Fraud detection using snapshots
echo "\n7. Fraud detection capabilities:\n";

// Check for unusual patterns
$largeTransactions = array_filter($transactionSnapshots, function($snapshot) {
    $data = $snapshot['data'] ?? $snapshot;
    $amount = $data['amount'] ?? 0;
    return $amount > 4000.00;
});

echo "   - Large transactions detected: ".count($largeTransactions)."\n";
foreach ($largeTransactions as $label => $snapshot) {
    $data = $snapshot['data'] ?? $snapshot;
    $amount = $data['amount'] ?? 'N/A';
    $type = $data['type'] ?? 'N/A';
    echo "     * {$label}: {$type} \${$amount}\n";
}

// Failed transaction analysis
$failedTransactions = array_filter($transactionSnapshots, function($snapshot) {
    $data = $snapshot['data'] ?? $snapshot;
    $status = $data['status'] ?? '';
    return $status === 'failed';
});

echo "   - Failed transactions: ".count($failedTransactions)."\n";

// Snapshot comparison for forensics
echo "\n8. Forensic analysis capabilities:\n";

// Compare account state before and after each transaction
$beforeDeposit = Snapshot::load('account-opened');
$afterDeposit = Snapshot::load('account-after-deposit');

if ($beforeDeposit && $afterDeposit) {
    $balanceChange = $afterDeposit['data']['balance'] - $beforeDeposit['data']['balance'];
    echo "   - Deposit impact: Balance changed by \${$balanceChange}\n";
}

$afterWithdrawal = Snapshot::load('account-after-withdrawal');
if ($afterDeposit && $afterWithdrawal) {
    $balanceChange = $afterWithdrawal['data']['balance'] - $afterDeposit['data']['balance'];
    echo "   - Withdrawal impact: Balance changed by \${$balanceChange}\n";
}

// Regulatory compliance features
echo "\n9. Regulatory compliance features:\n";
echo "   ✓ Complete transaction lifecycle tracking\n";
echo "   ✓ Immutable audit trail (snapshots cannot be modified)\n";
echo "   ✓ Timestamp integrity for all financial events\n";
echo "   ✓ Balance verification and reconciliation\n";
echo "   ✓ Failed transaction analysis\n";
echo "   ✓ Large transaction monitoring\n";
echo "   ✓ Point-in-time account state reconstruction\n";
echo "   ✓ Forensic analysis capabilities\n";

// Risk management insights
echo "\n10. Risk management insights:\n";
$totalTransactionValue = 0;
$successfulTransactions = 0;
$failedTransactions = count($failedTransactions);

foreach ($transactionSnapshots as $snapshot) {
    $data = $snapshot['data'] ?? $snapshot;
    if (($data['status'] ?? '') === 'completed') {
        $totalTransactionValue += $data['amount'] ?? 0;
        $successfulTransactions++;
    }
}

echo "   - Total transaction volume: \${$totalTransactionValue}\n";
echo "   - Successful transactions: {$successfulTransactions}\n";
echo "   - Failed transactions: {$failedTransactions}\n";
$successRate = $successfulTransactions / ($successfulTransactions + $failedTransactions) * 100;
echo "   - Transaction success rate: ".round($successRate, 2)."%\n";

// Cleanup
echo "\n11. Cleanup (in production, financial snapshots should be retained):\n";
echo "   Note: Financial snapshots typically have long retention periods\n";
echo "   for regulatory compliance (often 7+ years)\n";

$deletedCount = Snapshot::clear();
echo "    Test cleanup: Deleted {$deletedCount} snapshots\n";

echo "\n=== Financial Tracking Benefits Demonstrated ===\n";
echo "✓ Complete transaction lifecycle audit trail\n";
echo "✓ Balance integrity verification\n";
echo "✓ Regulatory compliance reporting\n";
echo "✓ Fraud detection and analysis\n";
echo "✓ Forensic investigation capabilities\n";
echo "✓ Risk management insights\n";
echo "✓ Immutable financial records\n";
echo "✓ Point-in-time financial state reconstruction\n";

echo "\nFinancial transaction tracking example completed successfully!\n";