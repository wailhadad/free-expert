<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use Carbon\Carbon;

echo "=== Testing Scheduler Auto-Renewal ===\n\n";

// 1. Get current active membership
$activeMembership = Membership::where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->first();

if (!$activeMembership) {
    echo "No active membership found!\n";
    exit;
}

echo "1. CURRENT ACTIVE MEMBERSHIP:\n";
echo "   ID: {$activeMembership->id}\n";
echo "   Seller: {$activeMembership->seller_id}\n";
echo "   Expires: {$activeMembership->expire_date}\n\n";

// 2. Expire the membership
echo "2. EXPIRING MEMBERSHIP...\n";
$originalExpiry = $activeMembership->expire_date;
$activeMembership->update(['expire_date' => Carbon::now()->subMinute()]);
echo "   ✓ Membership expired! (Changed from {$originalExpiry} to " . $activeMembership->fresh()->expire_date . ")\n\n";

// 3. Wait for scheduler to run
echo "3. WAITING FOR SCHEDULER TO RUN...\n";
echo "   The scheduler should run every minute and process this expired membership.\n";
echo "   You can check the logs or run 'php artisan memberships:process-expired' manually.\n\n";

echo "4. TO TEST AUTOMATICALLY:\n";
echo "   - Run: run_scheduler.bat (in background)\n";
echo "   - Or: .\\run_scheduler.ps1\n";
echo "   - Or set up Windows Task Scheduler\n\n";

echo "=== TEST COMPLETE ===\n";
echo "The membership is now expired and ready for auto-renewal!\n"; 