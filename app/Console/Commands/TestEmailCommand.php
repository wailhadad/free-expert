<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Helpers\BasicMailer;
use App\Models\BasicSettings\Basic;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Testing email functionality...');
        
        // Check SMTP configuration
        $smtpInfo = Basic::select('smtp_status', 'smtp_host', 'smtp_port', 'from_mail', 'from_name')->first();
        
        $this->info('SMTP Configuration:');
        $this->info('- SMTP Status: ' . ($smtpInfo->smtp_status ? 'Enabled' : 'Disabled'));
        $this->info('- SMTP Host: ' . $smtpInfo->smtp_host);
        $this->info('- SMTP Port: ' . $smtpInfo->smtp_port);
        $this->info('- From Email: ' . $smtpInfo->from_mail);
        $this->info('- From Name: ' . $smtpInfo->from_name);
        
        if (!$smtpInfo->smtp_status) {
            $this->error('SMTP is disabled! Emails will not be sent.');
            return 1;
        }
        
        // Try to send a test email
        try {
            $mailData = [
                'subject' => 'Test Email from FREE-EXPERT',
                'body' => 'This is a test email to verify that the email system is working properly.',
                'recipient' => $email,
                'sessionMessage' => 'Test email sent successfully!',
            ];
            
            BasicMailer::sendMail($mailData);
            
            $this->info('Test email sent successfully to: ' . $email);
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
            return 1;
        }
    }
} 