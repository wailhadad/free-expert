<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Transaction;
use App\Models\Seller;
use Carbon\Carbon;

echo "=== Testing Cron Job Functionality ===\n\n";

// 1. Check current active memberships
echo "1. Current Active Memberships:\n";
$activeMemberships = Membership::where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->get();

foreach ($activeMemberships as $m) {
    echo "   - ID: {$m->id}, Seller: {$m->seller_id}, Expires: {$m->expire_date}, Method: {$m->payment_method}\n";
}

// 2. Check expired memberships
echo "\n2. Expired Memberships:\n";
$expiredMemberships = Membership::where('status', 1)
    ->where('expire_date', '<', Carbon::now())
    ->get();

foreach ($expiredMemberships as $m) {
    echo "   - ID: {$m->id}, Seller: {$m->seller_id}, Expired: {$m->expire_date}, Method: {$m->payment_method}\n";
}

// 3. Count transactions before cron job
echo "\n3. Transactions Before Cron Job:\n";
$transactionsBefore = Transaction::where('seller_id', 42)->where('transcation_type', 5)->count();
echo "   - Total package purchase transactions for seller 42: {$transactionsBefore}\n";

// 4. Run the cron job
echo "\n4. Running Cron Job...\n";
$controller = new \App\Http\Controllers\CronJobController();
$controller->expired();
echo "   ✓ Cron job completed!\n";

// 5. Count transactions after cron job
echo "\n5. Transactions After Cron Job:\n";
$transactionsAfter = Transaction::where('seller_id', 42)->where('transcation_type', 5)->count();
echo "   - Total package purchase transactions for seller 42: {$transactionsAfter}\n";

if ($transactionsAfter > $transactionsBefore) {
    echo "   ✓ New transaction(s) created!\n";
} else {
    echo "   - No new transactions created (no expired memberships found)\n";
}

// 6. Check if any memberships were renewed
echo "\n6. Checking for Renewed Memberships:\n";
$renewedMemberships = Membership::where('seller_id', 42)
    ->where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->where('created_at', '>=', Carbon::now()->subMinutes(5))
    ->get();

foreach ($renewedMemberships as $m) {
    echo "   - ID: {$m->id}, Payment Method: {$m->payment_method}, Created: {$m->created_at}\n";
}

// 7. Test the artisan command
echo "\n7. Testing Artisan Command:\n";
$output = shell_exec('php artisan memberships:process-expired 2>&1');
echo "   Command output: " . trim($output) . "\n";

echo "\n=== Cron Job Test Complete ===\n";
echo "If you see new transactions or renewed memberships, the cron job is working!\n"; 