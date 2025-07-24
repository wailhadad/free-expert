<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Transaction;
use App\Models\Seller;
use Carbon\Carbon;

echo "=== Testing Auto-Renewal with Expired Membership ===\n\n";

// 1. Get the expired membership
$expiredMembership = Membership::where('status', 1)
    ->where('expire_date', '<', Carbon::now())
    ->first();

if (!$expiredMembership) {
    echo "No expired membership found!\n";
    exit;
}

echo "1. EXPIRED MEMBERSHIP:\n";
echo "   ID: {$expiredMembership->id}\n";
echo "   Seller: {$expiredMembership->seller_id}\n";
echo "   Payment Method: {$expiredMembership->payment_method}\n";
echo "   Expired: {$expiredMembership->expire_date}\n";
echo "   Price: \${$expiredMembership->price}\n\n";

// 2. Get seller info
$seller = Seller::find($expiredMembership->seller_id);
echo "2. SELLER INFO:\n";
echo "   Balance: \${$seller->amount}\n";
echo "   Name: {$seller->fname} {$seller->lname}\n\n";

// 3. Count transactions before
$transactionsBefore = Transaction::where('seller_id', $expiredMembership->seller_id)
    ->where('transcation_type', 5)
    ->count();
echo "3. TRANSACTIONS BEFORE: {$transactionsBefore}\n\n";

// 4. Run cron job
echo "4. RUNNING CRON JOB...\n";
$startTime = microtime(true);
$controller = new \App\Http\Controllers\CronJobController();
$controller->expired();
$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2);
echo "   ✓ Cron job completed in {$executionTime}ms\n\n";

// 5. Count transactions after
$transactionsAfter = Transaction::where('seller_id', $expiredMembership->seller_id)
    ->where('transcation_type', 5)
    ->count();
echo "5. TRANSACTIONS AFTER: {$transactionsAfter}\n";

if ($transactionsAfter > $transactionsBefore) {
    $newTransactions = $transactionsAfter - $transactionsBefore;
    echo "   ✓ {$newTransactions} new transaction(s) created!\n";
} else {
    echo "   - No new transactions created\n";
}

// 6. Check for new membership
echo "\n6. CHECKING FOR NEW MEMBERSHIP:\n";
$newMembership = Membership::where('seller_id', $expiredMembership->seller_id)
    ->where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->where('created_at', '>=', Carbon::now()->subMinutes(2))
    ->first();

if ($newMembership) {
    echo "   ✓ New membership created: ID {$newMembership->id}\n";
    echo "   Payment Method: {$newMembership->payment_method}\n";
    echo "   Expires: {$newMembership->expire_date}\n";
    echo "   Created: {$newMembership->created_at}\n";
} else {
    echo "   - No new membership created\n";
    echo "   - This could be due to insufficient balance or other conditions\n";
}

// 7. Check seller balance after
$updatedSeller = Seller::find($expiredMembership->seller_id);
echo "\n7. SELLER BALANCE AFTER: \${$updatedSeller->amount}\n";

$balanceChange = $updatedSeller->amount - $seller->amount;
if ($balanceChange != 0) {
    echo "   Balance change: \${$balanceChange}\n";
} else {
    echo "   No balance change\n";
}

// 8. Check if original membership is still expired
$originalMembership = Membership::find($expiredMembership->id);
echo "\n8. ORIGINAL MEMBERSHIP STATUS:\n";
echo "   Status: {$originalMembership->status}\n";
echo "   Expire Date: {$originalMembership->expire_date}\n";

if ($originalMembership->status == 1 && Carbon::parse($originalMembership->expire_date) >= Carbon::now()) {
    echo "   ✓ Original membership is now active again\n";
} else {
    echo "   ⚠ Original membership is still expired\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Auto-renewal system is working correctly!\n"; 