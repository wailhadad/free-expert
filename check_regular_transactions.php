<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\Membership;
use Carbon\Carbon;

echo "=== Checking Regular vs Auto-Renewal Transactions ===\n\n";

// Check all transactions for seller 42
$transactions = Transaction::where('seller_id', 42)
    ->where('transcation_type', 5) // Package purchase
    ->orderBy('created_at', 'desc')
    ->get();

echo "All Package Purchase Transactions for Seller 42:\n";
foreach ($transactions as $t) {
    echo "ID: {$t->id}, Payment Method: {$t->payment_method}, Amount: {$t->grand_total}, Date: {$t->created_at}\n";
    
    // Get the membership
    $membership = Membership::find($t->order_id);
    if ($membership) {
        echo "  - Membership ID: {$membership->id}, Membership Payment Method: {$membership->payment_method}\n";
    }
    echo "\n";
}

// Check all memberships for seller 42
echo "\nAll Memberships for Seller 42:\n";
$memberships = Membership::where('seller_id', 42)
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($memberships as $m) {
    echo "ID: {$m->id}, Payment Method: {$m->payment_method}, Status: {$m->status}, Price: {$m->price}, Created: {$m->created_at}\n";
} 