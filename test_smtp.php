<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BasicSettings\Basic;
use App\Http\Helpers\BasicMailer;

echo "=== SMTP Configuration Check ===\n";

// Check SMTP settings
$smtpInfo = Basic::select('smtp_status', 'smtp_host', 'smtp_port', 'from_mail', 'from_name')->first();

echo "SMTP Status: " . ($smtpInfo->smtp_status ? 'ENABLED' : 'DISABLED') . "\n";
echo "SMTP Host: " . $smtpInfo->smtp_host . "\n";
echo "SMTP Port: " . $smtpInfo->smtp_port . "\n";
echo "From Email: " . $smtpInfo->from_mail . "\n";
echo "From Name: " . $smtpInfo->from_name . "\n";

if ($smtpInfo->smtp_status) {
    echo "\n=== Testing Email Sending ===\n";
    
    $mailData = [
        'subject' => 'Test Email from FREE-EXPERT',
        'body' => 'This is a test email to verify that the email system is working properly.',
        'recipient' => 'test@example.com', // Change this to your email
        'sessionMessage' => 'Test email sent successfully!',
    ];
    
    try {
        BasicMailer::sendMail($mailData);
        echo "✅ Email sent successfully!\n";
    } catch (Exception $e) {
        echo "❌ Email sending failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n❌ SMTP is disabled! Please enable it in the admin panel.\n";
}

echo "\n=== Recent Log Entries ===\n";
$logFile = 'storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -20);
    foreach ($recentLines as $line) {
        if (strpos($line, 'BasicMailer') !== false || strpos($line, 'SMTP') !== false) {
            echo trim($line) . "\n";
        }
    }
} else {
    echo "Log file not found.\n";
} 