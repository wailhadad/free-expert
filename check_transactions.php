<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\SubscriptionLog;
use Carbon\Carbon;

echo "=== Recent Transactions ===\n";
$transactions = Transaction::latest()->take(5)->get(['id', 'seller_id', 'type', 'description', 'created_at']);

foreach ($transactions as $t) {
    echo "ID: {$t->id}, Seller: {$t->seller_id}, Type: {$t->type}, Description: {$t->description}, Date: {$t->created_at}\n";
}

echo "\n=== Recent Subscription Logs ===\n";
$logs = SubscriptionLog::latest()->take(5)->get(['id', 'seller_id', 'action', 'description', 'created_at']);

foreach ($logs as $l) {
    echo "ID: {$l->id}, Seller: {$l->seller_id}, Action: {$l->action}, Description: {$l->description}, Date: {$l->created_at}\n";
}

echo "\n=== Checking for auto-renewal transactions ===\n";
$autoTransactions = Transaction::where('type', 'membership_auto_renewal')->get();
echo "Auto-renewal transactions count: " . $autoTransactions->count() . "\n";

foreach ($autoTransactions as $t) {
    echo "Auto-renewal: ID {$t->id}, Seller {$t->seller_id}, Description: {$t->description}\n";
} 