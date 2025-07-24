<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use Carbon\Carbon;

// Get the active membership
$membership = Membership::where('status', 1)->first();

if ($membership) {
    echo "Current membership ID: " . $membership->id . "\n";
    echo "Current expiry date: " . $membership->expire_date . "\n";
    echo "Seller ID: " . $membership->seller_id . "\n";
    
    // Update expiry date to yesterday (expired)
    $membership->update(['expire_date' => Carbon::now()->subDay()]);
    
    echo "Updated expiry date: " . $membership->fresh()->expire_date . "\n";
    echo "Membership is now expired!\n";
    
    // Now test the auto-renewal by calling the cron job
    echo "\nTesting auto-renewal...\n";
    
    $controller = new \App\Http\Controllers\CronJobController();
    $controller->expired();
    
    echo "Auto-renewal process completed!\n";
    
    // Check if membership was renewed
    $renewedMembership = Membership::where('seller_id', $membership->seller_id)
        ->where('status', 1)
        ->where('start_date', '<=', Carbon::now())
        ->where('expire_date', '>=', Carbon::now())
        ->first();
    
    if ($renewedMembership) {
        echo "Membership was successfully renewed!\n";
        echo "New expiry date: " . $renewedMembership->expire_date . "\n";
    } else {
        echo "Membership was not renewed (possibly insufficient balance)\n";
    }
    
} else {
    echo "No active membership found!\n";
} 