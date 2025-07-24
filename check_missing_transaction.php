<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\Membership;

echo "=== Checking Missing Transaction ===\n\n";

// Check membership 179
$membership = Membership::find(179);
if ($membership) {
    echo "Membership 179:\n";
    echo "Payment Method: {$membership->payment_method}\n";
    echo "Status: {$membership->status}\n";
    echo "Price: {$membership->price}\n";
    echo "Created: {$membership->created_at}\n\n";
}

// Check if there's a transaction for membership 179
$transaction = Transaction::where('order_id', 179)->first();
if ($transaction) {
    echo "Transaction found for membership 179:\n";
    echo "ID: {$transaction->id}\n";
    echo "Payment Method: {$transaction->payment_method}\n";
    echo "Type: {$transaction->transcation_type}\n";
    echo "Amount: {$transaction->grand_total}\n";
} else {
    echo "NO TRANSACTION FOUND for membership 179!\n";
    echo "This is why the regular membership transaction is not showing up for the seller.\n";
}

echo "\n=== All transactions for seller 42 ===\n";
$transactions = Transaction::where('seller_id', 42)->get();
foreach ($transactions as $t) {
    echo "ID: {$t->id}, Order ID: {$t->order_id}, Type: {$t->transcation_type}, Method: {$t->payment_method}\n";
} 