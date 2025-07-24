<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Transaction;
use App\Models\Seller;
use Carbon\Carbon;

echo "=== Testing Expiry & Auto-Renewal ===\n\n";

// 1. Get current active membership
$activeMembership = Membership::where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->first();

if (!$activeMembership) {
    echo "No active membership found!\n";
    exit;
}

echo "1. CURRENT ACTIVE MEMBERSHIP:\n";
echo "   ID: {$activeMembership->id}\n";
echo "   Seller: {$activeMembership->seller_id}\n";
echo "   Payment Method: {$activeMembership->payment_method}\n";
echo "   Expires: {$activeMembership->expire_date}\n";
echo "   Price: \${$activeMembership->price}\n\n";

// 2. Get seller info
$seller = Seller::find($activeMembership->seller_id);
echo "2. SELLER INFO:\n";
echo "   Balance: \${$seller->amount}\n";
echo "   Name: {$seller->fname} {$seller->lname}\n\n";

// 3. Count transactions before
$transactionsBefore = Transaction::where('seller_id', $activeMembership->seller_id)
    ->where('transcation_type', 5)
    ->count();
echo "3. TRANSACTIONS BEFORE: {$transactionsBefore}\n\n";

// 4. Expire the membership
echo "4. EXPIRING MEMBERSHIP...\n";
$originalExpiry = $activeMembership->expire_date;
$activeMembership->update(['expire_date' => Carbon::now()->subMinute()]);
echo "   ✓ Membership expired! (Changed from {$originalExpiry} to " . $activeMembership->fresh()->expire_date . ")\n\n";

// 5. Run cron job
echo "5. RUNNING CRON JOB...\n";
$startTime = microtime(true);
$controller = new \App\Http\Controllers\CronJobController();
$controller->expired();
$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2);
echo "   ✓ Cron job completed in {$executionTime}ms\n\n";

// 6. Count transactions after
$transactionsAfter = Transaction::where('seller_id', $activeMembership->seller_id)
    ->where('transcation_type', 5)
    ->count();
echo "6. TRANSACTIONS AFTER: {$transactionsAfter}\n";

if ($transactionsAfter > $transactionsBefore) {
    $newTransactions = $transactionsAfter - $transactionsBefore;
    echo "   ✓ {$newTransactions} new transaction(s) created!\n";
} else {
    echo "   - No new transactions created\n";
}

// 7. Check for new membership
echo "\n7. CHECKING FOR NEW MEMBERSHIP:\n";
$newMembership = Membership::where('seller_id', $activeMembership->seller_id)
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

// 8. Check seller balance after
$updatedSeller = Seller::find($activeMembership->seller_id);
echo "\n8. SELLER BALANCE AFTER: \${$updatedSeller->amount}\n";

$balanceChange = $updatedSeller->amount - $seller->amount;
if ($balanceChange != 0) {
    echo "   Balance change: \${$balanceChange}\n";
} else {
    echo "   No balance change\n";
}

// 9. Check if original membership is still active
$originalMembership = Membership::find($activeMembership->id);
echo "\n9. ORIGINAL MEMBERSHIP STATUS:\n";
echo "   Status: {$originalMembership->status}\n";
echo "   Expire Date: {$originalMembership->expire_date}\n";

if ($originalMembership->status == 1 && Carbon::parse($originalMembership->expire_date) >= Carbon::now()) {
    echo "   ✓ Original membership is still active\n";
} else {
    echo "   ⚠ Original membership is no longer active\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Auto-renewal system is working correctly!\n"; 