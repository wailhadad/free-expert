<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\Membership;
use App\Models\BasicSettings\Basic;

echo "=== Fixing Missing Transaction ===\n\n";

// Get membership 179
$membership = Membership::find(179);
if (!$membership) {
    echo "Membership 179 not found!\n";
    exit;
}

echo "Membership 179 found:\n";
echo "Payment Method: {$membership->payment_method}\n";
echo "Price: {$membership->price}\n";
echo "Status: {$membership->status}\n\n";

// Check if transaction already exists
$existingTransaction = Transaction::where('order_id', 179)->first();
if ($existingTransaction) {
    echo "Transaction already exists for membership 179!\n";
    echo "Transaction ID: {$existingTransaction->id}\n";
    exit;
}

// Get basic settings
$bs = Basic::first();

// Create the missing transaction
$transaction = Transaction::create([
    'transcation_id' => uniqid(),
    'order_id' => $membership->id,
    'transcation_type' => 5, // Package purchase
    'user_id' => null,
    'seller_id' => $membership->seller_id,
    'payment_status' => 'completed',
    'payment_method' => $membership->payment_method,
    'grand_total' => $membership->price,
    'pre_balance' => null,
    'tax' => null,
    'after_balance' => null,
    'gateway_type' => 'online',
    'currency_symbol' => $membership->currency_symbol,
    'currency_symbol_position' => $bs->base_currency_symbol_position,
]);

echo "Transaction created successfully!\n";
echo "Transaction ID: {$transaction->id}\n";
echo "Payment Method: {$transaction->payment_method}\n";
echo "Amount: {$transaction->grand_total}\n";

echo "\n=== Verification ===\n";
$verificationTransaction = Transaction::where('order_id', 179)->first();
if ($verificationTransaction) {
    echo "✓ Transaction verified for membership 179\n";
    echo "Transaction ID: {$verificationTransaction->id}\n";
    echo "Payment Method: {$verificationTransaction->payment_method}\n";
} else {
    echo "✗ Transaction not found for membership 179\n";
} 