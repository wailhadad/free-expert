<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Seller;
use App\Models\Package;
use App\Services\NotificationService;
use Carbon\Carbon;

echo "=== Duplicate Grace Period Notification Fix Test ===\n\n";

// 1. Check current seller memberships
echo "1. CURRENT SELLER MEMBERSHIPS:\n";
$memberships = Membership::with(['seller', 'package'])->get();

if ($memberships->count() > 0) {
    foreach ($memberships as $m) {
        $status = '';
        if ($m->status == 1 && !$m->isTrulyExpired()) {
            if ($m->isInGracePeriod()) {
                $status = 'GRACE PERIOD';
                $timeRemaining = $m->getGracePeriodTimeRemaining();
                $status .= " (Time remaining: " . \App\Http\Helpers\GracePeriodHelper::formatTimeRemaining($timeRemaining) . ")";
            } else {
                $status = 'ACTIVE';
            }
        } elseif ($m->status == 1 && $m->isTrulyExpired()) {
            $status = 'EXPIRED';
        } else {
            $status = 'INACTIVE';
        }
        
        $sellerName = $m->seller ? $m->seller->username : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   ID: {$m->id}, Seller: {$sellerName}, Package: {$packageTitle}, Status: {$m->status}, Expires: {$m->expire_date} ({$status})\n";
        echo "   └─ In Grace Period: " . ($m->in_grace_period ? 'Yes' : 'No') . ", Grace Until: " . ($m->grace_period_until ?? 'N/A') . "\n";
    }
} else {
    echo "   - No seller memberships found\n";
}

// 2. Test the fixed query logic
echo "\n2. TESTING FIXED QUERY LOGIC:\n";

// Simulate the fixed query from CronJobController
$expired_members = Membership::where('status', 1)
    ->where('expire_date', '<', Carbon::now())
    ->where(function($query) {
        $query->whereNull('processed_for_renewal')
              ->orWhere('processed_for_renewal', 0);
    })
    ->where(function($query) {
        $query->whereNull('in_grace_period')
              ->orWhere('in_grace_period', 0);
    })
    ->get();

echo "   Memberships that need to enter grace period: {$expired_members->count()}\n";

if ($expired_members->count() > 0) {
    foreach ($expired_members as $m) {
        $sellerName = $m->seller ? $m->seller->username : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   └─ ID: {$m->id}, Seller: {$sellerName}, Package: {$packageTitle}\n";
        echo "      └─ Expire Date: {$m->expire_date}, In Grace: " . ($m->in_grace_period ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "   └─ No memberships need to enter grace period\n";
}

// 3. Test memberships already in grace period
echo "\n3. TESTING MEMBERSHIPS ALREADY IN GRACE PERIOD:\n";

$grace_period_members = Membership::where('status', 1)
    ->where('in_grace_period', 1)
    ->where('grace_period_until', '>', Carbon::now())
    ->get();

echo "   Memberships currently in grace period: {$grace_period_members->count()}\n";

if ($grace_period_members->count() > 0) {
    foreach ($grace_period_members as $m) {
        $sellerName = $m->seller ? $m->seller->username : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        $timeRemaining = $m->getGracePeriodTimeRemaining();
        echo "   └─ ID: {$m->id}, Seller: {$sellerName}, Package: {$packageTitle}\n";
        echo "      └─ Grace Until: {$m->grace_period_until}, Time Remaining: " . \App\Http\Helpers\GracePeriodHelper::formatTimeRemaining($timeRemaining) . "\n";
    }
} else {
    echo "   └─ No memberships currently in grace period\n";
}

// 4. Test truly expired memberships (after grace period)
echo "\n4. TESTING TRULY EXPIRED MEMBERSHIPS:\n";

$truly_expired_members = Membership::where('status', 1)
    ->where('in_grace_period', 1)
    ->where('grace_period_until', '<', Carbon::now())
    ->where(function($query) {
        $query->whereNull('processed_for_renewal')
              ->orWhere('processed_for_renewal', 0);
    })
    ->get();

echo "   Memberships that have truly expired (after grace period): {$truly_expired_members->count()}\n";

if ($truly_expired_members->count() > 0) {
    foreach ($truly_expired_members as $m) {
        $sellerName = $m->seller ? $m->seller->username : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   └─ ID: {$m->id}, Seller: {$sellerName}, Package: {$packageTitle}\n";
        echo "      └─ Grace Period Ended: {$m->grace_period_until}\n";
    }
} else {
    echo "   └─ No memberships have truly expired\n";
}

// 5. Test the complete flow simulation
echo "\n5. SIMULATING COMPLETE FLOW:\n";

// Find a membership that could be used for testing
$testMembership = Membership::where('status', 1)
    ->where('expire_date', '>', Carbon::now())
    ->first();

if ($testMembership) {
    echo "   Found test membership ID: {$testMembership->id}\n";
    echo "   Current expire date: {$testMembership->expire_date}\n";
    echo "   Current in_grace_period: " . ($testMembership->in_grace_period ? 'Yes' : 'No') . "\n";
    
    // Simulate what happens when it expires
    $originalExpireDate = $testMembership->expire_date;
    $testMembership->update(['expire_date' => Carbon::now()->subMinute()]);
    echo "   Updated expire date to: {$testMembership->fresh()->expire_date}\n";
    
    // Now test the query
    $expired_members = Membership::where('status', 1)
        ->where('expire_date', '<', Carbon::now())
        ->where(function($query) {
            $query->whereNull('processed_for_renewal')
                  ->orWhere('processed_for_renewal', 0);
        })
        ->where(function($query) {
            $query->whereNull('in_grace_period')
                  ->orWhere('in_grace_period', 0);
        })
        ->get();
    
    echo "   Query finds {$expired_members->count()} memberships to enter grace period\n";
    
    if ($expired_members->contains('id', $testMembership->id)) {
        echo "   ✓ Test membership is correctly identified for grace period entry\n";
        
        // Simulate starting grace period
        $testMembership->startGracePeriod(2);
        echo "   Started grace period until: {$testMembership->fresh()->grace_period_until}\n";
        echo "   In grace period: " . ($testMembership->fresh()->in_grace_period ? 'Yes' : 'No') . "\n";
        
        // Test the query again - should not find it anymore
        $expired_members_after = Membership::where('status', 1)
            ->where('expire_date', '<', Carbon::now())
            ->where(function($query) {
                $query->whereNull('processed_for_renewal')
                      ->orWhere('processed_for_renewal', 0);
            })
            ->where(function($query) {
                $query->whereNull('in_grace_period')
                      ->orWhere('in_grace_period', 0);
            })
            ->get();
        
        echo "   Query after grace period start finds {$expired_members_after->count()} memberships\n";
        
        if (!$expired_members_after->contains('id', $testMembership->id)) {
            echo "   ✓ Test membership is correctly excluded from grace period entry query\n";
            echo "   ✓ Duplicate notification fix is working correctly!\n";
        } else {
            echo "   ✗ Test membership is still being found - fix may not be working\n";
        }
    } else {
        echo "   ✗ Test membership is not being found by the query\n";
    }
    
    // Restore original expire date
    $testMembership->update(['expire_date' => $originalExpireDate]);
    echo "   Restored original expire date: {$testMembership->fresh()->expire_date}\n";
    
} else {
    echo "   No suitable test membership found\n";
}

echo "\n=== Test Complete ===\n";
echo "\nSUMMARY:\n";
echo "- The fix adds a condition to exclude memberships already in grace period\n";
echo "- This prevents duplicate grace period notifications on subsequent cron runs\n";
echo "- The query now properly separates: (1) memberships needing to enter grace period vs (2) memberships already in grace period\n"; 