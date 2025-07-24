<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Seller;
use App\Models\Package;
use App\Services\NotificationService;
use Carbon\Carbon;

echo "=== Seller Membership Real-time Notifications Test ===\n\n";

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
        if ($m->grace_period_until) {
            echo "   └─ Grace Period Until: {$m->grace_period_until}\n";
        }
    }
} else {
    echo "   - No seller memberships found\n";
}

// 2. Test real-time notification service
echo "\n2. TESTING REAL-TIME NOTIFICATION SERVICE:\n";
$notificationService = new NotificationService();

// Find a seller to test with
$seller = Seller::first();
if ($seller) {
    echo "   Testing with seller: {$seller->username} (ID: {$seller->id})\n";
    
    // Test grace period notification
    echo "   Sending grace period notification...\n";
    $notificationData = [
        'type' => 'seller_membership_grace_period',
        'title' => 'Membership in Grace Period',
        'message' => "Your membership for package 'Test Package' is now in grace period. Please add funds within 2 minutes to avoid losing access.",
        'url' => route('seller.plan.extend.index'),
        'icon' => 'fas fa-clock',
        'extra' => [
            'membership_id' => 1,
            'package_id' => 1,
            'package_title' => 'Test Package',
            'expire_date' => Carbon::now()->subMinute(),
            'grace_period_until' => Carbon::now()->addMinutes(2),
            'grace_period_minutes' => 2,
        ]
    ];

    try {
        $notificationService->sendRealTime($seller, $notificationData);
        echo "   ✓ Grace period notification sent successfully\n";
    } catch (\Exception $e) {
        echo "   ✗ Error sending grace period notification: " . $e->getMessage() . "\n";
    }
    
    // Test expiration notification
    echo "   Sending expiration notification...\n";
    $notificationData = [
        'type' => 'seller_membership_expired',
        'title' => 'Membership Expired',
        'message' => "Your membership for package 'Test Package' has expired. Please renew to continue accessing premium features.",
        'url' => route('seller.plan.extend.index'),
        'icon' => 'fas fa-calendar-times',
        'extra' => [
            'membership_id' => 1,
            'package_id' => 1,
            'package_title' => 'Test Package',
            'expire_date' => Carbon::now()->subMinute(),
        ]
    ];

    try {
        $notificationService->sendRealTime($seller, $notificationData);
        echo "   ✓ Expiration notification sent successfully\n";
    } catch (\Exception $e) {
        echo "   ✗ Error sending expiration notification: " . $e->getMessage() . "\n";
    }
    
    // Test reminder notification
    echo "   Sending reminder notification...\n";
    $notificationData = [
        'type' => 'seller_membership_reminder',
        'title' => 'Membership Expiring Soon',
        'message' => "Your membership for package 'Test Package' will expire on " . Carbon::now()->addDays(7) . ". Please renew to avoid service interruption.",
        'url' => route('seller.plan.extend.index'),
        'icon' => 'fas fa-clock',
        'extra' => [
            'membership_id' => 1,
            'package_id' => 1,
            'package_title' => 'Test Package',
            'expire_date' => Carbon::now()->addDays(7),
            'days_remaining' => 7,
        ]
    ];

    try {
        $notificationService->sendRealTime($seller, $notificationData);
        echo "   ✓ Reminder notification sent successfully\n";
    } catch (\Exception $e) {
        echo "   ✗ Error sending reminder notification: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "   - No sellers found for testing\n";
}

// 3. Test cron job
echo "\n3. TESTING CRON JOB:\n";
echo "   Running seller membership cron job...\n";

try {
    $controller = new \App\Http\Controllers\CronJobController();
    $controller->expired();
    echo "   ✓ Cron job completed successfully\n";
} catch (\Exception $e) {
    echo "   ✗ Error running cron job: " . $e->getMessage() . "\n";
}

// 4. Check notification database
echo "\n4. CHECKING NOTIFICATION DATABASE:\n";
try {
    $notifications = \DB::table('notifications')
        ->where('notifiable_type', 'App\\Models\\Seller')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($notifications->count() > 0) {
        echo "   Recent seller notifications:\n";
        foreach ($notifications as $notification) {
            $data = json_decode($notification->data, true);
            echo "   - ID: {$notification->id}, Type: {$data['type']}, Title: {$data['title']}, Created: {$notification->created_at}\n";
        }
    } else {
        echo "   - No recent seller notifications found\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error checking notifications: " . $e->getMessage() . "\n";
}

echo "\n=== Test Completed ===\n"; 