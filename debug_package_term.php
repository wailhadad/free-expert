<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Package;
use Carbon\Carbon;

echo "=== Debug Package Term in Auto-Renewal ===\n\n";

// 1. Check current expired membership
$expiredMembership = Membership::where('status', 1)
    ->where('expire_date', '<', Carbon::now())
    ->first();

if (!$expiredMembership) {
    echo "No expired membership found!\n";
    exit;
}

echo "1. EXPIRED MEMBERSHIP:\n";
echo "   ID: {$expiredMembership->id}\n";
echo "   Seller: {$expiredMembership->seller_id}\n";
echo "   Package ID: {$expiredMembership->package_id}\n";
echo "   Expired: {$expiredMembership->expire_date}\n\n";

// 2. Check the package
$package = Package::find($expiredMembership->package_id);
if ($package) {
    echo "2. PACKAGE DETAILS:\n";
    echo "   ID: {$package->id}\n";
    echo "   Title: {$package->title}\n";
    echo "   Term: {$package->term}\n";
    echo "   Price: \${$package->price}\n\n";
} else {
    echo "2. PACKAGE NOT FOUND!\n\n";
}

// 3. Check modification request
$seller = $expiredMembership->seller;
$modRequest = $seller->membershipModificationRequests()->where('status', 'pending')->latest('requested_at')->first();

if ($modRequest) {
    echo "3. MODIFICATION REQUEST FOUND:\n";
    echo "   ID: {$modRequest->id}\n";
    echo "   Package ID: {$modRequest->package_id}\n";
    $modPackage = Package::find($modRequest->package_id);
    if ($modPackage) {
        echo "   Package Title: {$modPackage->title}\n";
        echo "   Package Term: {$modPackage->term}\n";
    }
    echo "\n";
} else {
    echo "3. NO MODIFICATION REQUEST FOUND\n\n";
}

// 4. Simulate the auto-renewal logic
echo "4. SIMULATING AUTO-RENEWAL LOGIC:\n";
$finalPackage = $modRequest ? $modRequest->package : $expiredMembership->package;
echo "   Final Package ID: {$finalPackage->id}\n";
echo "   Final Package Title: {$finalPackage->title}\n";
echo "   Final Package Term: {$finalPackage->term}\n\n";

// 5. Calculate expiry date
$now = Carbon::now();
$expireDate = null;

if ($finalPackage->term == 'monthly') {
    $expireDate = $now->copy()->addMonth();
    echo "5. CALCULATED EXPIRY (Monthly): {$expireDate}\n";
} elseif ($finalPackage->term == 'yearly') {
    $expireDate = $now->copy()->addYear();
    echo "5. CALCULATED EXPIRY (Yearly): {$expireDate}\n";
} elseif ($finalPackage->term == 'lifetime') {
    $expireDate = Carbon::maxValue();
    echo "5. CALCULATED EXPIRY (Lifetime): {$expireDate}\n";
} else {
    echo "5. UNKNOWN TERM: {$finalPackage->term}\n";
}

echo "\n=== DEBUG COMPLETE ===\n"; 