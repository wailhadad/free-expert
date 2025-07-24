<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\UserMembership;
use App\Models\Membership;
use App\Models\User;
use App\Models\Seller;
use App\Models\UserPackage;
use App\Models\Package;
use Carbon\Carbon;

echo "=== Grace Period System Test ===\n\n";

// 1. Check current user memberships
echo "1. CURRENT USER MEMBERSHIPS:\n";
$userMemberships = UserMembership::with(['user', 'package'])->get();

if ($userMemberships->count() > 0) {
    foreach ($userMemberships as $m) {
        $status = '';
        if ($m->status == '1' && !$m->isTrulyExpired()) {
            if ($m->isInGracePeriod()) {
                $status = 'GRACE PERIOD';
                $timeRemaining = $m->getGracePeriodTimeRemaining();
                $status .= " (Time remaining: " . \App\Http\Helpers\GracePeriodHelper::formatTimeRemaining($timeRemaining) . ")";
            } else {
                $status = 'ACTIVE';
            }
        } elseif ($m->status == '1' && $m->isTrulyExpired()) {
            $status = 'EXPIRED';
        } else {
            $status = 'INACTIVE';
        }
        
        $userName = $m->user ? $m->user->username : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   ID: {$m->id}, User: {$userName}, Package: {$packageTitle}, Status: {$m->status}, Expires: {$m->expire_date} ({$status})\n";
        if ($m->grace_period_until) {
            echo "   └─ Grace Period Until: {$m->grace_period_until}\n";
        }
    }
} else {
    echo "   - No user memberships found\n";
}

// 2. Check current seller memberships
echo "\n2. CURRENT SELLER MEMBERSHIPS:\n";
$sellerMemberships = Membership::with(['seller', 'package'])->get();

if ($sellerMemberships->count() > 0) {
    foreach ($sellerMemberships as $m) {
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
        
        $sellerName = $m->seller ? ($m->seller->fname . ' ' . $m->seller->lname) : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   ID: {$m->id}, Seller: {$sellerName}, Package: {$packageTitle}, Status: {$m->status}, Expires: {$m->expire_date} ({$status})\n";
        if ($m->grace_period_until) {
            echo "   └─ Grace Period Until: {$m->grace_period_until}\n";
        }
    }
} else {
    echo "   - No seller memberships found\n";
}

// 3. Test grace period helper functions
echo "\n3. GRACE PERIOD HELPER TESTS:\n";

// Test for users
$users = User::limit(3)->get();
foreach ($users as $user) {
    $graceData = \App\Http\Helpers\GracePeriodHelper::getUserGracePeriodCountdown($user->id);
    if ($graceData) {
        echo "   User {$user->username} is in grace period: {$graceData['formatted_time']} remaining\n";
    }
}

// Test for sellers
$sellers = Seller::limit(3)->get();
foreach ($sellers as $seller) {
    $graceData = \App\Http\Helpers\GracePeriodHelper::getSellerGracePeriodCountdown($seller->id);
    if ($graceData) {
        echo "   Seller {$seller->fname} {$seller->lname} is in grace period: {$graceData['formatted_time']} remaining\n";
    }
}

// 4. Test grace period settings
echo "\n4. GRACE PERIOD SETTINGS:\n";
$settings = \App\Http\Helpers\GracePeriodHelper::getGracePeriodSettings();
echo "   Grace Period Minutes: {$settings['grace_period_minutes']}\n";
echo "   Is Enabled: " . ($settings['is_enabled'] ? 'Yes' : 'No') . "\n";

// 5. Test creating a grace period (if we have active memberships)
echo "\n5. TESTING GRACE PERIOD CREATION:\n";
$activeUserMembership = UserMembership::where('status', '1')
    ->where('expire_date', '>', Carbon::now())
    ->first();

if ($activeUserMembership) {
    echo "   Found active user membership ID: {$activeUserMembership->id}\n";
    echo "   Current expire date: {$activeUserMembership->expire_date}\n";
    
    // Set it to expire 1 minute ago to test grace period
    $activeUserMembership->update(['expire_date' => Carbon::now()->subMinute()]);
    echo "   Updated expire date to: {$activeUserMembership->fresh()->expire_date}\n";
    
    // Start grace period
    $activeUserMembership->startGracePeriod(2);
    echo "   Started grace period until: {$activeUserMembership->fresh()->grace_period_until}\n";
    
    $timeRemaining = $activeUserMembership->getGracePeriodTimeRemaining();
    if ($timeRemaining) {
        echo "   Time remaining: " . \App\Http\Helpers\GracePeriodHelper::formatTimeRemaining($timeRemaining) . "\n";
    }
} else {
    echo "   No active user memberships found to test\n";
}

echo "\n=== Test Complete ===\n"; 