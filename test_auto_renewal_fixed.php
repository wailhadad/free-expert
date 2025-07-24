<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Transaction;
use App\Models\Seller;
use Carbon\Carbon;

echo "=== Testing Fixed Auto-Renewal System ===\n\n";

// 1. Check current active membership
$membership = Membership::where('status', 1)->first();
if (!$membership) {
    echo "No active membership found!\n";
    exit;
}

echo "1. Current Membership:\n";
echo "   ID: {$membership->id}\n";
echo "   Seller ID: {$membership->seller_id}\n";
echo "   Expiry Date: {$membership->expire_date}\n";
echo "   Status: {$membership->status}\n\n";

// 2. Get seller info
$seller = Seller::find($membership->seller_id);
echo "2. Seller Info:\n";
echo "   Name: {$seller->fname} {$seller->lname}\n";
echo "   Email: {$seller->email}\n";
echo "   Balance: {$seller->amount}\n\n";

// 3. Expire the membership
echo "3. Expiring membership...\n";
$membership->update(['expire_date' => Carbon::now()->subDay()]);
echo "   New expiry date: {$membership->fresh()->expire_date}\n\n";

// 4. Check if membership is now expired
$expiredMembership = Membership::where('id', $membership->id)
    ->where('expire_date', '<', Carbon::now())
    ->first();

if ($expiredMembership) {
    echo "4. Membership is now expired ✓\n\n";
} else {
    echo "4. Error: Membership is not expired!\n\n";
    exit;
}

// 5. Run auto-renewal process
echo "5. Running auto-renewal process...\n";
$controller = new \App\Http\Controllers\CronJobController();
$controller->expired();
echo "   Auto-renewal process completed ✓\n\n";

// 6. Check if new membership was created
$newMembership = Membership::where('seller_id', $membership->seller_id)
    ->where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->latest()
    ->first();

if ($newMembership) {
    echo "6. New membership created ✓\n";
    echo "   New membership ID: {$newMembership->id}\n";
    echo "   New expiry date: {$newMembership->expire_date}\n\n";
} else {
    echo "6. No new membership created (possibly insufficient balance)\n\n";
}

// 7. Check for transaction records
echo "7. Checking transaction records...\n";
$transactions = Transaction::where('seller_id', $membership->seller_id)
    ->where('payment_method', 'balance_auto')
    ->latest()
    ->take(3)
    ->get();

echo "   Auto-renewal transactions count: {$transactions->count()}\n";
foreach ($transactions as $t) {
    echo "   - ID: {$t->id}, Type: {$t->transcation_type}, Method: {$t->payment_method}, Amount: {$t->grand_total}\n";
}

// 8. Check seller balance
echo "\n8. Checking seller balance...\n";
$updatedSeller = Seller::find($membership->seller_id);
echo "   New balance: {$updatedSeller->amount}\n";

echo "\n=== Test Complete ===\n"; 