<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Seller;
use App\Models\Package;
use Carbon\Carbon;

echo "=== Buy Membership Button Test ===\n\n";

// 1. Check current seller memberships and conditions
echo "1. CURRENT SELLER MEMBERSHIPS AND CONDITIONS:\n";
$memberships = Membership::with(['seller', 'package'])->get();

if ($memberships->count() > 0) {
    foreach ($memberships as $m) {
        $seller = $m->seller;
        if (!$seller) continue;
        
        $status = '';
        if ($m->status == 1 && !$m->isTrulyExpired()) {
            if ($m->isInGracePeriod()) {
                $status = 'GRACE PERIOD';
            } else {
                $status = 'ACTIVE';
            }
        } elseif ($m->status == 1 && $m->isTrulyExpired()) {
            $status = 'EXPIRED';
        } else {
            $status = 'INACTIVE';
        }
        
        $sellerName = $seller->username;
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   ID: {$m->id}, Seller: {$sellerName}, Package: {$packageTitle}, Status: {$m->status}, Expires: {$m->expire_date} ({$status})\n";
        echo "   └─ In Grace Period: " . ($m->in_grace_period ? 'Yes' : 'No') . ", Grace Until: " . ($m->grace_period_until ?? 'N/A') . "\n";
        echo "   └─ Pending Payment: " . ($m->pending_payment ? 'Yes' : 'No') . ", Processed for Renewal: " . ($m->processed_for_renewal ? 'Yes' : 'No') . "\n";
        echo "   └─ Seller Balance: $" . number_format($seller->amount, 2) . "\n";
    }
} else {
    echo "   - No seller memberships found\n";
}

// 2. Test the dashboard button logic
echo "\n2. TESTING DASHBOARD BUTTON LOGIC:\n";

$sellers = Seller::all();
foreach ($sellers as $seller) {
    echo "   Seller: {$seller->username}\n";
    echo "   └─ Balance: $" . number_format($seller->amount, 2) . "\n";
    
    // Check current membership
    $currentMembership = Membership::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('start_date', '<=', Carbon::now())
        ->where('expire_date', '>=', Carbon::now())
        ->first();
    
    echo "   └─ Current Membership: " . ($currentMembership ? "Yes (ID: {$currentMembership->id})" : "No") . "\n";
    
    // Check pending payment membership
    $pendingPaymentMembership = Membership::where('seller_id', $seller->id)
        ->where('pending_payment', true)
        ->orderBy('id', 'DESC')
        ->first();
    
    echo "   └─ Pending Payment Membership: " . ($pendingPaymentMembership ? "Yes (ID: {$pendingPaymentMembership->id})" : "No") . "\n";
    
    // Test the button condition
    $showButton = ($pendingPaymentMembership && !$currentMembership) || (!$currentMembership && $seller->amount < 0);
    echo "   └─ Show Buy Membership Button: " . ($showButton ? "YES" : "No") . "\n";
    
    if ($showButton) {
        echo "   └─ Reason: ";
        if ($pendingPaymentMembership && !$currentMembership) {
            echo "Pending payment membership exists and no current membership";
        } elseif (!$currentMembership && $seller->amount < 0) {
            echo "No current membership and negative balance";
        }
        echo "\n";
    }
    echo "\n";
}

// 3. Test setting pending_payment for expired memberships with negative balance
echo "3. TESTING PENDING_PAYMENT SETTING:\n";

$expiredMemberships = Membership::where('status', 1)
    ->where('in_grace_period', 1)
    ->where('grace_period_until', '<', Carbon::now())
    ->where('processed_for_renewal', 0)
    ->get();

echo "   Found {$expiredMemberships->count()} expired memberships that need processing\n";

foreach ($expiredMemberships as $m) {
    $seller = $m->seller;
    if (!$seller) continue;
    
    echo "   └─ Membership ID: {$m->id}, Seller: {$seller->username}\n";
    echo "      └─ Current Balance: $" . number_format($seller->amount, 2) . "\n";
    echo "      └─ Package Price: $" . number_format($m->package->price, 2) . "\n";
    
    // Simulate what would happen in the cron job
    $originalBalance = $seller->amount;
    $newBalance = $originalBalance - $m->package->price;
    $balanceWentNegative = $newBalance < 0;
    
    echo "      └─ Would set pending_payment: " . ($balanceWentNegative ? "Yes" : "No") . "\n";
    echo "      └─ New balance would be: $" . number_format($newBalance, 2) . "\n";
}

// 4. Test the complete flow simulation
echo "\n4. SIMULATING COMPLETE FLOW:\n";

// Find a membership that could be used for testing
$testMembership = Membership::where('status', 1)
    ->where('expire_date', '>', Carbon::now())
    ->first();

if ($testMembership) {
    $seller = $testMembership->seller;
    echo "   Found test membership ID: {$testMembership->id} for seller: {$seller->username}\n";
    echo "   Current expire date: {$testMembership->expire_date}\n";
    echo "   Current balance: $" . number_format($seller->amount, 2) . "\n";
    
    // Simulate what happens when it expires
    $originalExpireDate = $testMembership->expire_date;
    $testMembership->update(['expire_date' => Carbon::now()->subMinute()]);
    echo "   Updated expire date to: {$testMembership->fresh()->expire_date}\n";
    
    // Simulate starting grace period
    $testMembership->startGracePeriod(2);
    echo "   Started grace period until: {$testMembership->fresh()->grace_period_until}\n";
    
    // Simulate grace period expiring
    $testMembership->update(['grace_period_until' => Carbon::now()->subMinute()]);
    echo "   Grace period expired at: {$testMembership->fresh()->grace_period_until}\n";
    
    // Simulate the cron job processing
    $originalBalance = $seller->amount;
    $packagePrice = $testMembership->package->price;
    $newBalance = $originalBalance - $packagePrice;
    
    echo "   Original balance: $" . number_format($originalBalance, 2) . "\n";
    echo "   Package price: $" . number_format($packagePrice, 2) . "\n";
    echo "   New balance would be: $" . number_format($newBalance, 2) . "\n";
    echo "   Balance went negative: " . ($newBalance < 0 ? "Yes" : "No") . "\n";
    
    // Simulate setting pending_payment
    $testMembership->update([
        'processed_for_renewal' => 1,
        'pending_payment' => $newBalance < 0
    ]);
    
    echo "   Set pending_payment: " . ($testMembership->fresh()->pending_payment ? "Yes" : "No") . "\n";
    
    // Test the button condition again
    $currentMembership = Membership::where('seller_id', $seller->id)
        ->where('status', 1)
        ->where('start_date', '<=', Carbon::now())
        ->where('expire_date', '>=', Carbon::now())
        ->first();
    
    $pendingPaymentMembership = Membership::where('seller_id', $seller->id)
        ->where('pending_payment', true)
        ->orderBy('id', 'DESC')
        ->first();
    
    $showButton = ($pendingPaymentMembership && !$currentMembership) || (!$currentMembership && $seller->amount < 0);
    echo "   Show Buy Membership Button: " . ($showButton ? "YES" : "No") . "\n";
    
    // Restore original expire date
    $testMembership->update(['expire_date' => $originalExpireDate]);
    echo "   Restored original expire date: {$testMembership->fresh()->expire_date}\n";
    
} else {
    echo "   No suitable test membership found\n";
}

echo "\n=== Test Complete ===\n";
echo "\nSUMMARY:\n";
echo "- Added 'pending_payment' to Membership model fillable array\n";
echo "- Updated CronJobController to set pending_payment=true when balance goes negative\n";
echo "- Updated dashboard logic to show Buy Membership button when balance < 0 and no current membership\n";
echo "- The button should now appear for sellers with negative balance and expired memberships\n"; 