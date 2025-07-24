<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use Carbon\Carbon;

echo "=== Checking Auto-Renewal Memberships ===\n\n";

// Check all memberships for seller 42
$memberships = Membership::where('seller_id', 42)->orderBy('id', 'desc')->get();

echo "ALL MEMBERSHIPS FOR SELLER 42:\n";
foreach ($memberships as $m) {
    $isExpired = Carbon::parse($m->expire_date)->lt(Carbon::now());
    $expiredText = $isExpired ? ' (EXPIRED)' : ' (ACTIVE)';
    
    echo "ID: {$m->id}\n";
    echo "   Status: {$m->status}\n";
    echo "   Payment Method: {$m->payment_method}\n";
    echo "   Processed for Renewal: " . ($m->processed_for_renewal ?? 'NULL') . "\n";
    echo "   Expires: {$m->expire_date}{$expiredText}\n";
    echo "   Created: {$m->created_at}\n";
    echo "   ---\n";
}

echo "\n=== SUMMARY ===\n";
$activeMemberships = $memberships->filter(function($m) {
    return Carbon::parse($m->expire_date)->gt(Carbon::now());
});

$expiredMemberships = $memberships->filter(function($m) {
    return Carbon::parse($m->expire_date)->lt(Carbon::now());
});

echo "Active Memberships: " . $activeMemberships->count() . "\n";
echo "Expired Memberships: " . $expiredMemberships->count() . "\n";

if ($activeMemberships->count() > 0) {
    echo "\n✅ AUTO-RENEWAL WORKED! There are active memberships.\n";
} else {
    echo "\n⚠ NO ACTIVE MEMBERSHIPS FOUND. Auto-renewal may have failed.\n";
} 