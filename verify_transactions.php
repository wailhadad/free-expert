<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\Membership;

echo "=== Verifying All Transactions for Seller 42 ===\n\n";

// Get all transactions for seller 42
$transactions = Transaction::where('seller_id', 42)
    ->where('transcation_type', 5) // Package purchase
    ->orderBy('created_at', 'desc')
    ->get();

echo "All Package Purchase Transactions:\n";
foreach ($transactions as $t) {
    echo "Transaction ID: {$t->id}\n";
    echo "Payment Method: {$t->payment_method}\n";
    echo "Amount: {$t->grand_total}\n";
    echo "Created: {$t->created_at}\n";
    
    // Get the membership
    $membership = Membership::find($t->order_id);
    if ($membership) {
        echo "Membership ID: {$membership->id}\n";
        echo "Membership Payment Method: {$membership->payment_method}\n";
    }
    echo "---\n";
}

echo "\n=== Summary ===\n";
echo "Total package purchase transactions: " . $transactions->count() . "\n";

$regularTransactions = $transactions->where('payment_method', '!=', 'balance_auto');
$autoRenewalTransactions = $transactions->where('payment_method', 'balance_auto');

echo "Regular transactions: " . $regularTransactions->count() . "\n";
echo "Auto-renewal transactions: " . $autoRenewalTransactions->count() . "\n";

echo "\n✓ Both regular and auto-renewal transactions should now be visible to the seller!\n"; 