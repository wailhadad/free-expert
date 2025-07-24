<?php

echo "=== Laravel Cron Job Status Check ===\n\n";

// Check if Laravel scheduler is running
echo "1. CHECKING LARAVEL SCHEDULER:\n";
$schedulerRunning = false;

// Check if the scheduler process is running
$output = shell_exec('tasklist /FI "IMAGENAME eq php.exe" 2>nul');
if (strpos($output, 'php.exe') !== false) {
    echo "   ✓ PHP processes are running\n";
    $schedulerRunning = true;
} else {
    echo "   ⚠ No PHP processes found\n";
}

echo "\n2. MANUAL TEST OF CRON COMMAND:\n";
echo "   Running: php artisan memberships:process-expired\n";
$output = shell_exec('php artisan memberships:process-expired 2>&1');
echo "   Output: " . trim($output) . "\n";

echo "\n3. SETUP INSTRUCTIONS:\n";
echo "   To ensure the cron job runs automatically, you need to:\n\n";
echo "   A. For Windows (using Task Scheduler):\n";
echo "      1. Open Task Scheduler\n";
echo "      2. Create Basic Task\n";
echo "      3. Name: 'Laravel Scheduler'\n";
echo "      4. Trigger: Daily, repeat every 1 minute\n";
echo "      5. Action: Start a program\n";
echo "      6. Program: php\n";
echo "      7. Arguments: artisan schedule:run\n";
echo "      8. Start in: " . getcwd() . "\n\n";

echo "   B. For Linux/Unix:\n";
echo "      1. Add to crontab: * * * * * cd " . getcwd() . " && php artisan schedule:run >> /dev/null 2>&1\n\n";

echo "   C. Alternative - Run manually every minute:\n";
echo "      php artisan schedule:run\n\n";

echo "4. TESTING SCHEDULER:\n";
echo "   Running: php artisan schedule:run\n";
$output = shell_exec('php artisan schedule:run 2>&1');
echo "   Output: " . trim($output) . "\n";

echo "\n=== CHECK COMPLETE ===\n";
echo "If the scheduler is not running automatically, use the setup instructions above.\n"; 