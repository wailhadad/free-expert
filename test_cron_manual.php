<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\CronJobController;

echo "=== Testing Cron Job Manually ===\n\n";

echo "Running expired membership processing...\n";
$controller = new CronJobController();
$controller->expired();

echo "Cron job completed!\n";
echo "Check your seller dashboard to see if expired memberships were processed.\n"; 