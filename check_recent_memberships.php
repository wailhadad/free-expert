<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Membership;
use App\Models\Package;
use Carbon\Carbon;

echo "=== Check Recent Memberships ===\n\n";

// Get recent memberships (last 10)
$recentMemberships = Membership::orderBy('id', 'desc')->limit(10)->get();

echo "RECENT MEMBERSHIPS:\n";
foreach ($recentMemberships as $m) {
    $package = Package::find($m->package_id);
    $packageTitle = $package ? $package->title : 'Unknown';
    $packageTerm = $package ? $package->term : 'Unknown';
    
    $duration = '';
    if ($m->start_date && $m->expire_date) {
        $start = Carbon::parse($m->start_date);
        $expire = Carbon::parse($m->expire_date);
        $diff = $start->diffInMinutes($expire);
        $duration = "({$diff} minutes)";
    }
    
    echo "ID: {$m->id}, Seller: {$m->seller_id}, Package: {$packageTitle} ({$packageTerm})\n";
    echo "   Start: {$m->start_date}\n";
    echo "   Expire: {$m->expire_date} {$duration}\n";
    echo "   Method: {$m->payment_method}\n";
    echo "   Status: {$m->status}\n";
    echo "   Created: {$m->created_at}\n";
    echo "   ---\n";
}

echo "\n=== CHECK COMPLETE ===\n"; 