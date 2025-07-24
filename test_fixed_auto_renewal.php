<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Transaction;
use App\Models\Seller;
use Carbon\Carbon;

echo "=== Testing Fixed Auto-Renewal System ===\n\n";

// 1. Check current expired memberships
echo "1. CHECKING EXPIRED MEMBERSHIPS:\n";
$expiredMemberships = Membership::where('status', 1)
    ->where('expire_date', '<', Carbon::now())
    ->get();

if ($expiredMemberships->count() > 0) {
    foreach ($expiredMemberships as $m) {
        echo "   ⚠ ID: {$m->id}, Seller: {$m->seller_id}, Expired: {$m->expire_date}\n";
    }
} else {
    echo "   - No expired memberships found\n";
}

// 2. Count transactions before
$transactionsBefore = Transaction::where('transcation_type', 5)->count();
echo "\n2. TRANSACTIONS BEFORE: {$transactionsBefore}\n";

// 3. Run the fixed cron job
echo "\n3. RUNNING FIXED CRON JOB...\n";
$startTime = microtime(true);
$controller = new \App\Http\Controllers\CronJobController();
$controller->expired();
$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2);
echo "   ✓ Cron job completed in {$executionTime}ms\n";

// 4. Count transactions after
$transactionsAfter = Transaction::where('transcation_type', 5)->count();
echo "\n4. TRANSACTIONS AFTER: {$transactionsAfter}\n";

if ($transactionsAfter > $transactionsBefore) {
    $newTransactions = $transactionsAfter - $transactionsBefore;
    echo "   ✓ {$newTransactions} new transaction(s) created!\n";
} else {
    echo "   - No new transactions created\n";
}

// 5. Check for new memberships
echo "\n5. CHECKING FOR NEW MEMBERSHIPS:\n";
$newMemberships = Membership::where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->where('created_at', '>=', Carbon::now()->subMinutes(2))
    ->get();

if ($newMemberships->count() > 0) {
    foreach ($newMemberships as $m) {
        echo "   ✓ New membership: ID {$m->id}, Seller: {$m->seller_id}, Method: {$m->payment_method}\n";
        echo "   Expires: {$m->expire_date}, Created: {$m->created_at}\n";
    }
} else {
    echo "   - No new memberships created\n";
}

// 6. Check seller balances
echo "\n6. SELLER BALANCES:\n";
$sellersWithMemberships = Seller::whereHas('memberships', function($query) {
    $query->where('status', 1);
})->get();

foreach ($sellersWithMemberships as $seller) {
    echo "   - Seller {$seller->id}: \${$seller->amount}\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Auto-renewal system should now work correctly!\n"; 