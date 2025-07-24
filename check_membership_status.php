<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use Carbon\Carbon;

echo "=== Current Membership Status ===\n\n";

// Check all memberships
$allMemberships = Membership::orderBy('id', 'desc')->get();

echo "ALL MEMBERSHIPS:\n";
foreach ($allMemberships as $m) {
    $status = '';
    if ($m->status == 1 && Carbon::parse($m->expire_date) >= Carbon::now()) {
        $status = 'ACTIVE';
    } elseif ($m->status == 1 && Carbon::parse($m->expire_date) < Carbon::now()) {
        $status = 'EXPIRED';
    } else {
        $status = 'INACTIVE';
    }
    
    echo "ID: {$m->id}, Seller: {$m->seller_id}, Status: {$m->status}, Expires: {$m->expire_date} ({$status})\n";
}

echo "\n=== ACTIVE MEMBERSHIPS ===\n";
$activeMemberships = Membership::where('status', 1)
    ->where('start_date', '<=', Carbon::now())
    ->where('expire_date', '>=', Carbon::now())
    ->get();

if ($activeMemberships->count() > 0) {
    foreach ($activeMemberships as $m) {
        echo "✓ ID: {$m->id}, Seller: {$m->seller_id}, Expires: {$m->expire_date}\n";
    }
} else {
    echo "No active memberships found!\n";
}

echo "\n=== EXPIRED MEMBERSHIPS ===\n";
$expiredMemberships = Membership::where('status', 1)
    ->where('expire_date', '<', Carbon::now())
    ->get();

if ($expiredMemberships->count() > 0) {
    foreach ($expiredMemberships as $m) {
        echo "⚠ ID: {$m->id}, Seller: {$m->seller_id}, Expired: {$m->expire_date}\n";
    }
} else {
    echo "No expired memberships found!\n";
} 