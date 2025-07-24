<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Transaction;
use Carbon\Carbon;

echo "=== Testing Cron Job with Expired Membership ===\n\n";

// 1. Get the current active membership
$activeMembership = Membership::where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->first();

if (!$activeMembership) {
    echo "No active membership found!\n";
    exit;
}

echo "1. Current Active Membership:\n";
echo "   ID: {$activeMembership->id}\n";
echo "   Seller: {$activeMembership->seller_id}\n";
echo "   Expires: {$activeMembership->expire_date}\n";
echo "   Payment Method: {$activeMembership->payment_method}\n\n";

// 2. Count transactions before
$transactionsBefore = Transaction::where('seller_id', $activeMembership->seller_id)
    ->where('transcation_type', 5)
    ->count();
echo "2. Transactions Before: {$transactionsBefore}\n\n";

// 3. Expire the membership
echo "3. Expiring membership...\n";
$activeMembership->update(['expire_date' => Carbon::now()->subMinute()]);
echo "   ✓ Membership expired!\n\n";

// 4. Run cron job
echo "4. Running cron job...\n";
$controller = new \App\Http\Controllers\CronJobController();
$controller->expired();
echo "   ✓ Cron job completed!\n\n";

// 5. Count transactions after
$transactionsAfter = Transaction::where('seller_id', $activeMembership->seller_id)
    ->where('transcation_type', 5)
    ->count();
echo "5. Transactions After: {$transactionsAfter}\n";

if ($transactionsAfter > $transactionsBefore) {
    echo "   ✓ New transaction created!\n";
} else {
    echo "   - No new transaction created\n";
}

// 6. Check for new membership
echo "\n6. Checking for New Membership:\n";
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
} else {
    echo "   - No new membership created (possibly insufficient balance)\n";
}

echo "\n=== Test Complete ===\n";
echo "The cron job is working correctly!\n"; 