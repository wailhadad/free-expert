<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Transaction;
use App\Models\Seller;
use Carbon\Carbon;

echo "=== Auto-Renewal & Expiry Detection Monitor ===\n";
echo "Monitoring every minute...\n";
echo "Press Ctrl+C to stop\n\n";

$monitorCount = 0;

while (true) {
    $monitorCount++;
    $currentTime = Carbon::now();
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "MONITOR CYCLE #{$monitorCount} - {$currentTime->format('Y-m-d H:i:s')}\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // 1. Check current active memberships
    echo "1. ACTIVE MEMBERSHIPS:\n";
    $activeMemberships = Membership::where('status', 1)
        ->where('start_date', '<=', Carbon::now())
        ->where('expire_date', '>=', Carbon::now())
        ->get();
    
    if ($activeMemberships->count() > 0) {
        foreach ($activeMemberships as $m) {
            $timeUntilExpiry = Carbon::parse($m->expire_date)->diffForHumans();
            echo "   ✓ ID: {$m->id}, Seller: {$m->seller_id}, Expires: {$m->expire_date} ({$timeUntilExpiry})\n";
        }
    } else {
        echo "   - No active memberships found\n";
    }
    
    // 2. Check expired memberships
    echo "\n2. EXPIRED MEMBERSHIPS:\n";
    $expiredMemberships = Membership::where('status', 1)
        ->where('expire_date', '<', Carbon::now())
        ->get();
    
    if ($expiredMemberships->count() > 0) {
        foreach ($expiredMemberships as $m) {
            $timeSinceExpiry = Carbon::parse($m->expire_date)->diffForHumans();
            echo "   ⚠ ID: {$m->id}, Seller: {$m->seller_id}, Expired: {$m->expire_date} ({$timeSinceExpiry})\n";
        }
    } else {
        echo "   - No expired memberships found\n";
    }
    
    // 3. Count transactions before cron job
    echo "\n3. TRANSACTIONS BEFORE CRON JOB:\n";
    $transactionsBefore = Transaction::where('transcation_type', 5)->count();
    echo "   - Total package purchase transactions: {$transactionsBefore}\n";
    
    // 4. Run cron job
    echo "\n4. RUNNING CRON JOB:\n";
    $startTime = microtime(true);
    $controller = new \App\Http\Controllers\CronJobController();
    $controller->expired();
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    echo "   ✓ Cron job completed in {$executionTime}ms\n";
    
    // 5. Count transactions after cron job
    echo "\n5. TRANSACTIONS AFTER CRON JOB:\n";
    $transactionsAfter = Transaction::where('transcation_type', 5)->count();
    echo "   - Total package purchase transactions: {$transactionsAfter}\n";
    
    if ($transactionsAfter > $transactionsBefore) {
        $newTransactions = $transactionsAfter - $transactionsBefore;
        echo "   ✓ {$newTransactions} new transaction(s) created!\n";
    } else {
        echo "   - No new transactions created\n";
    }
    
    // 6. Check for newly created memberships
    echo "\n6. NEWLY CREATED MEMBERSHIPS (Last 2 minutes):\n";
    $newMemberships = Membership::where('created_at', '>=', Carbon::now()->subMinutes(2))
        ->where('status', 1)
        ->get();
    
    if ($newMemberships->count() > 0) {
        foreach ($newMemberships as $m) {
            echo "   ✓ ID: {$m->id}, Seller: {$m->seller_id}, Method: {$m->payment_method}, Created: {$m->created_at}\n";
        }
    } else {
        echo "   - No new memberships created\n";
    }
    
    // 7. Check seller balances
    echo "\n7. SELLER BALANCES:\n";
    $sellersWithMemberships = Seller::whereHas('memberships', function($query) {
        $query->where('status', 1);
    })->get();
    
    foreach ($sellersWithMemberships as $seller) {
        echo "   - Seller {$seller->id}: \${$seller->amount}\n";
    }
    
    // 8. System Status
    echo "\n8. SYSTEM STATUS:\n";
    echo "   - Active Memberships: " . $activeMemberships->count() . "\n";
    echo "   - Expired Memberships: " . $expiredMemberships->count() . "\n";
    echo "   - Total Transactions: " . $transactionsAfter . "\n";
    echo "   - New Memberships: " . $newMemberships->count() . "\n";
    
    echo "\n" . str_repeat("-", 60) . "\n";
    echo "Next check in 60 seconds...\n";
    echo str_repeat("-", 60) . "\n";
    
    // Wait for 60 seconds
    sleep(60);
} 