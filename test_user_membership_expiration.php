<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\UserMembership;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;

echo "=== User Membership Expiration System Test ===\n\n";

// 1. Check current user memberships
echo "1. CURRENT USER MEMBERSHIPS:\n";
$userMemberships = UserMembership::with(['user', 'package'])->get();

if ($userMemberships->count() > 0) {
    foreach ($userMemberships as $m) {
        $status = '';
        if ($m->status == '1' && Carbon::parse($m->expire_date) >= Carbon::now()) {
            $status = 'ACTIVE';
        } elseif ($m->status == '1' && Carbon::parse($m->expire_date) < Carbon::now()) {
            $status = 'EXPIRED';
        } else {
            $status = 'INACTIVE';
        }
        
        $userName = $m->user ? $m->user->username : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   ID: {$m->id}, User: {$userName}, Package: {$packageTitle}, Status: {$m->status}, Expires: {$m->expire_date} ({$status})\n";
    }
} else {
    echo "   - No user memberships found\n";
}

// 2. Check for expired memberships
echo "\n2. EXPIRED USER MEMBERSHIPS:\n";
$expiredMemberships = UserMembership::where('status', '1')
    ->where('expire_date', '<', Carbon::now())
    ->where(function($query) {
        $query->whereNull('processed_for_expiration')
              ->orWhere('processed_for_expiration', 0);
    })
    ->with(['user', 'package'])
    ->get();

if ($expiredMemberships->count() > 0) {
    foreach ($expiredMemberships as $m) {
        $userName = $m->user ? $m->user->username : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   ⚠ ID: {$m->id}, User: {$userName}, Package: {$packageTitle}, Expired: {$m->expire_date}\n";
    }
} else {
    echo "   - No expired user memberships found\n";
}

// 3. Check for reminder memberships
echo "\n3. USER MEMBERSHIPS FOR REMINDER:\n";
$bs = \App\Models\BasicSettings\Basic::first();
$reminderDays = $bs->expiration_reminder ?? 7;

$reminderMemberships = UserMembership::where('status', '1')
    ->whereDate('expire_date', Carbon::now()->addDays($reminderDays))
    ->where(function($query) {
        $query->whereNull('reminder_sent')
              ->orWhere('reminder_sent', 0);
    })
    ->with(['user', 'package'])
    ->get();

if ($reminderMemberships->count() > 0) {
    foreach ($reminderMemberships as $m) {
        $userName = $m->user ? $m->user->username : 'Unknown';
        $packageTitle = $m->package ? $m->package->title : 'Unknown';
        echo "   ⏰ ID: {$m->id}, User: {$userName}, Package: {$packageTitle}, Expires: {$m->expire_date}\n";
    }
} else {
    echo "   - No user memberships for reminder found\n";
}

// 4. Test the command
echo "\n4. TESTING COMMAND:\n";
echo "   Running: php artisan user-memberships:process-expirations\n";
$output = shell_exec('php artisan user-memberships:process-expirations 2>&1');
echo "   Output: " . trim($output) . "\n";

// 5. Check results after processing
echo "\n5. RESULTS AFTER PROCESSING:\n";

$processedExpired = UserMembership::where('status', '1')
    ->where('expire_date', '<', Carbon::now())
    ->where('processed_for_expiration', 1)
    ->count();

$processedReminders = UserMembership::where('status', '1')
    ->where('reminder_sent', 1)
    ->count();

echo "   - Expired memberships processed: {$processedExpired}\n";
echo "   - Reminder notifications sent: {$processedReminders}\n";

// 6. Test with a sample membership (if exists)
if ($userMemberships->count() > 0) {
    echo "\n6. TESTING WITH SAMPLE MEMBERSHIP:\n";
    $sampleMembership = $userMemberships->first();
    
    echo "   Original expiry date: {$sampleMembership->expire_date}\n";
    
    // Temporarily expire the membership
    $originalExpiry = $sampleMembership->expire_date;
    $sampleMembership->update([
        'expire_date' => Carbon::now()->subDay(),
        'processed_for_expiration' => 0
    ]);
    
    echo "   Updated expiry date: {$sampleMembership->fresh()->expire_date}\n";
    
    // Run the command again
    echo "   Running command again...\n";
    $output = shell_exec('php artisan user-memberships:process-expirations 2>&1');
    echo "   Output: " . trim($output) . "\n";
    
    // Check if it was processed
    $sampleMembership->refresh();
    if ($sampleMembership->processed_for_expiration == 1) {
        echo "   ✓ Sample membership was processed successfully!\n";
    } else {
        echo "   - Sample membership was not processed\n";
    }
    
    // Restore original expiry date
    $sampleMembership->update([
        'expire_date' => $originalExpiry,
        'processed_for_expiration' => 0
    ]);
    echo "   Restored original expiry date\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "The user membership expiration system is now active!\n";
echo "It will run every minute via the Laravel scheduler.\n"; 