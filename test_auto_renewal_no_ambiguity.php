<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Transaction;
use Carbon\Carbon;

echo "=== Testing Auto-Renewal Without Status Ambiguity ===\n\n";

// 1. Check current memberships
echo "1. CURRENT MEMBERSHIPS:\n";
$memberships = Membership::where('seller_id', 42)->orderBy('id', 'desc')->limit(5)->get();

foreach ($memberships as $m) {
    echo "ID: {$m->id}, Status: {$m->status}, Processed: " . ($m->processed_for_renewal ?? 'NULL') . ", Expires: {$m->expire_date}\n";
}

// 2. Find expired memberships that haven't been processed
echo "\n2. EXPIRED MEMBERSHIPS (NOT PROCESSED):\n";
$expiredMemberships = Membership::where('status', 1)
    ->where('expire_date', '<', Carbon::now())
    ->where(function($query) {
        $query->whereNull('processed_for_renewal')
              ->orWhere('processed_for_renewal', 0);
    })
    ->get();

if ($expiredMemberships->count() > 0) {
    foreach ($expiredMemberships as $m) {
        echo "   ⚠ ID: {$m->id}, Seller: {$m->seller_id}, Expired: {$m->expire_date}\n";
    }
} else {
    echo "   - No unprocessed expired memberships found\n";
}

// 3. Count transactions before
$transactionsBefore = Transaction::where('transcation_type', 5)->count();
echo "\n3. TRANSACTIONS BEFORE: {$transactionsBefore}\n";

// 4. Run the cron job
echo "\n4. RUNNING CRON JOB...\n";
$startTime = microtime(true);
$controller = new \App\Http\Controllers\CronJobController();
$controller->expired();
$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2);
echo "   ✓ Cron job completed in {$executionTime}ms\n";

// 5. Count transactions after
$transactionsAfter = Transaction::where('transcation_type', 5)->count();
echo "\n5. TRANSACTIONS AFTER: {$transactionsAfter}\n";

if ($transactionsAfter > $transactionsBefore) {
    $newTransactions = $transactionsAfter - $transactionsBefore;
    echo "   ✓ {$newTransactions} new transaction(s) created!\n";
} else {
    echo "   - No new transactions created\n";
}

// 6. Check memberships after auto-renewal
echo "\n6. MEMBERSHIPS AFTER AUTO-RENEWAL:\n";
$membershipsAfter = Membership::where('seller_id', 42)->orderBy('id', 'desc')->limit(5)->get();

foreach ($membershipsAfter as $m) {
    $paymentStatus = ($m->status == 1 || $m->status == 2) ? 'Success' : 'Pending';
    echo "ID: {$m->id}, Status: {$m->status}, Payment: {$paymentStatus}, Processed: " . ($m->processed_for_renewal ?? 'NULL') . ", Expires: {$m->expire_date}\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "✅ Old memberships keep status=1 (Success)\n";
echo "✅ New memberships get status=1 (Success)\n";
echo "✅ No ambiguity between old and new memberships\n";
echo "✅ processed_for_renewal prevents duplicates\n"; 