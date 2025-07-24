<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Package;
use Carbon\Carbon;

echo "=== Check All Recent Memberships ===\n\n";

// Get all memberships created in the last hour
$recentMemberships = Membership::where('created_at', '>=', Carbon::now()->subHour())
    ->orderBy('id', 'desc')
    ->get();

echo "MEMBERSHIPS CREATED IN LAST HOUR:\n";
foreach ($recentMemberships as $m) {
    $package = Package::find($m->package_id);
    $packageTitle = $package ? $package->title : 'Unknown';
    $packageTerm = $package ? $package->term : 'Unknown';
    
    $duration = '';
    if ($m->start_date && $m->expire_date) {
        $start = Carbon::parse($m->start_date);
        $expire = Carbon::parse($m->expire_date);
        $diffMinutes = $start->diffInMinutes($expire);
        $diffDays = $start->diffInDays($expire);
        
        if ($diffMinutes < 60) {
            $duration = "({$diffMinutes} minutes) - SHORT!";
        } elseif ($diffDays < 1) {
            $duration = "({$diffMinutes} minutes) - SHORT!";
        } else {
            $duration = "({$diffDays} days)";
        }
    }
    
    echo "ID: {$m->id}, Seller: {$m->seller_id}, Package: {$packageTitle} ({$packageTerm})\n";
    echo "   Start: {$m->start_date}\n";
    echo "   Expire: {$m->expire_date} {$duration}\n";
    echo "   Method: {$m->payment_method}\n";
    echo "   Status: {$m->status}\n";
    echo "   Created: {$m->created_at}\n";
    echo "   ---\n";
}

echo "\n=== SUMMARY ===\n";
$shortMemberships = $recentMemberships->filter(function($m) {
    if ($m->start_date && $m->expire_date) {
        $start = Carbon::parse($m->start_date);
        $expire = Carbon::parse($m->expire_date);
        return $start->diffInMinutes($expire) < 60;
    }
    return false;
});

if ($shortMemberships->count() > 0) {
    echo "⚠ FOUND {$shortMemberships->count()} MEMBERSHIP(S) WITH SHORT DURATION:\n";
    foreach ($shortMemberships as $m) {
        echo "   ID: {$m->id}, Method: {$m->payment_method}\n";
    }
} else {
    echo "✅ ALL RECENT MEMBERSHIPS HAVE PROPER DURATION\n";
}

echo "\n=== CHECK COMPLETE ===\n"; 