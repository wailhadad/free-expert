<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Helpers\BasicMailer;
use App\Models\BasicSettings\Basic;
use App\Models\ClientService\ServiceOrder;
use App\Models\User;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email?} {--check-orders : Check for orders with empty email addresses} {--fix-emails : Fix orders with empty email addresses} {--check-invoices : Check and generate missing invoices} {--update-invoices : Update database with existing invoice filenames} {--debug-transactions : Debug transactions to see why some show "No invoice"} {--fix-invoices : Fix specific invoice filename issues} {--move-invoices : Move invoices from old service directory to new order-invoices directory} {--check-specific : Check specific transactions by their IDs} {--fix-all-invoices : Fix all missing invoice database entries} {--check-single : Check a single specific transaction} {--check-order-invoice : Check a specific order\'s invoice field} {--schedule : Show how to create a scheduled task for fixing missing invoices} {--membership-invoices : Check and fix missing membership invoices} {--check-specific-transaction : Check a specific transaction by its ID} {--fix-missing-seller-id : Fix transactions with missing seller_id} {--fix-payment-controllers : Fix payment controllers to not delete invoice files} {--test-automatic-invoices : Test automatic invoice generation for new seller memberships} {--fix-all-transactions : Fix all transactions with missing seller_id or user_id} {--list-memberships : List all user and seller memberships with their IDs} {--fix-orphaned-transactions : Fix transactions with non-existent order_ids by linking them to existing memberships} {--fix-specific-transaction : Fix a specific transaction by linking it to an existing membership} {--quick-fix-transaction : Quick fix a specific transaction by its ID} {--debug-invoices : Check all memberships and their invoice files for mismatches} {--generate-missing-invoices : Generate missing invoice files for all memberships} {--fix-membership-invoice : Fix a specific membership invoice by regenerating it} {--check-payment-methods : Check payment methods for membership transactions} {--fix-payment-methods : Fix payment methods for membership transactions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending functionality and check/fix orders with empty emails';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('check-orders')) {
            return $this->checkOrdersWithEmptyEmails();
        }

        if ($this->option('fix-emails')) {
            return $this->fixOrdersWithEmptyEmails();
        }

        if ($this->option('check-invoices')) {
            return $this->checkMissingInvoices();
        }

        if ($this->option('update-invoices')) {
            return $this->updateInvoiceDatabase();
        }

        if ($this->option('debug-transactions')) {
            return $this->debugTransactions();
        }

        if ($this->option('fix-invoices')) {
            return $this->fixInvoiceFilenames();
        }

        if ($this->option('move-invoices')) {
            return $this->moveInvoices();
        }

        if ($this->option('check-specific')) {
            return $this->checkSpecificTransactions();
        }

        if ($this->option('fix-all-invoices')) {
            return $this->fixAllInvoices();
        }

        if ($this->option('check-single')) {
            $transactionId = $this->argument('email'); // Assuming email argument is the transaction ID for single check
            if (!$transactionId) {
                $this->error('Transaction ID is required for --check-single option.');
                return 1;
            }
            return $this->checkSingleTransaction($transactionId);
        }

        if ($this->option('check-order-invoice')) {
            $orderNumber = $this->argument('email'); // Assuming email argument is the order number for single check
            if (!$orderNumber) {
                $this->error('Order number is required for --check-order-invoice option.');
                return 1;
            }
            return $this->checkOrderInvoice($orderNumber);
        }

        if ($this->option('schedule')) {
            return $this->createScheduledTask();
        }

        if ($this->option('membership-invoices')) {
            return $this->checkMembershipInvoices();
        }

        if ($this->option('check-specific-transaction')) {
            $transactionId = $this->argument('email');
            return $this->checkSpecificTransaction($transactionId);
        }

        if ($this->option('fix-missing-seller-id')) {
            return $this->fixMissingSellerId();
        }

        if ($this->option('fix-all-transactions')) {
            return $this->fixAllTransactions();
        }

        if ($this->option('list-memberships')) {
            return $this->listMemberships();
        }

        if ($this->option('fix-orphaned-transactions')) {
            return $this->fixOrphanedTransactions();
        }

        if ($this->option('fix-specific-transaction')) {
            return $this->fixSpecificTransaction($this->argument('email'));
        }

        if ($this->option('quick-fix-transaction')) {
            return $this->quickFixTransaction();
        }

        if ($this->option('debug-invoices')) {
            return $this->debugInvoices();
        }

        if ($this->option('generate-missing-invoices')) {
            return $this->generateMissingInvoices();
        }

        if ($this->option('fix-payment-controllers')) {
            return $this->fixPaymentControllers();
        }

        if ($this->option('test-automatic-invoices')) {
            return $this->testAutomaticInvoices();
        }

        if ($this->option('fix-membership-invoice')) {
            return $this->fixMembershipInvoice($this->argument('email'));
        }

        if ($this->option('check-payment-methods')) {
            return $this->checkPaymentMethods();
        }

        if ($this->option('fix-payment-methods')) {
            return $this->fixPaymentMethods();
        }

        $email = $this->argument('email');
        
        if (!$email) {
            $this->error('Email address is required for testing.');
            return 1;
        }
        
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

    /**
     * Check for orders with empty email addresses
     */
    private function checkOrdersWithEmptyEmails()
    {
        $this->info('Checking for orders with empty email addresses...');
        
        $ordersWithEmptyEmails = ServiceOrder::whereNull('email_address')
            ->orWhere('email_address', '')
            ->orWhere('email_address', 'N/A')
            ->get();
        
        if ($ordersWithEmptyEmails->count() == 0) {
            $this->info('No orders found with empty email addresses.');
            return 0;
        }
        
        $this->warn("Found {$ordersWithEmptyEmails->count()} orders with empty email addresses:");
        
        foreach ($ordersWithEmptyEmails as $order) {
            $this->line("- Order #{$order->order_number} (ID: {$order->id}) - Customer: {$order->name}");
        }
        
        $this->info('Run "php artisan test:email --fix-emails" to fix these orders.');
        return 0;
    }

    /**
     * Fix orders with empty email addresses
     */
    private function fixOrdersWithEmptyEmails()
    {
        $this->info('Fixing orders with empty email addresses...');
        
        $ordersWithEmptyEmails = ServiceOrder::whereNull('email_address')
            ->orWhere('email_address', '')
            ->orWhere('email_address', 'N/A')
            ->get();
        
        if ($ordersWithEmptyEmails->count() == 0) {
            $this->info('No orders found with empty email addresses.');
            return 0;
        }
        
        $fixed = 0;
        
        foreach ($ordersWithEmptyEmails as $order) {
            // Try to get email from user
            $user = User::find($order->user_id);
            if ($user && !empty($user->email_address)) {
                $order->email_address = $user->email_address;
                $order->save();
                $this->info("Fixed Order #{$order->order_number} - Email: {$user->email_address}");
                $fixed++;
            } else {
                $this->warn("Could not fix Order #{$order->order_number} - No valid user email found");
            }
        }
        
        $this->info("Fixed {$fixed} out of {$ordersWithEmptyEmails->count()} orders.");
        return 0;
    }

    /**
     * Check and generate missing invoices
     */
    private function checkMissingInvoices()
    {
        $this->info('Checking for orders with missing invoices...');
        
        $ordersWithoutInvoices = \App\Models\ClientService\ServiceOrder::whereNull('invoice')
            ->orWhere('invoice', '')
            ->get();
            
        if ($ordersWithoutInvoices->count() == 0) {
            $this->info('All orders have invoices!');
            return;
        }
        
        $this->info("Found {$ordersWithoutInvoices->count()} orders without invoices.");
        
        foreach ($ordersWithoutInvoices as $order) {
            try {
                $this->info("Generating invoice for order #{$order->order_number}...");
                
                // Generate invoice
                $invoiceName = $this->generateInvoice($order);
                
                // Update order with invoice name
                $order->update(['invoice' => $invoiceName]);
                
                $this->info("Invoice generated: {$invoiceName}");
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for order #{$order->order_number}: " . $e->getMessage());
            }
        }
        
        $this->info('Invoice generation completed!');
    }
    
    /**
     * Generate invoice for an order
     */
    private function generateInvoice($order)
    {
        $invoiceName = $order->order_number . '.pdf';
        $directory = 'assets/file/invoices/order-invoices/';
        $fullDirectory = public_path($directory);
        
        if (!file_exists($fullDirectory)) {
            mkdir($fullDirectory, 0775, true);
        }
        
        $fileLocation = $directory . $invoiceName;
        $arrData['orderInfo'] = $order;

        // Get website info for logo and title
        $websiteInfo = \App\Models\BasicSettings\Basic::first();
        $arrData['orderInfo']->logo = $websiteInfo->logo;
        $arrData['orderInfo']->website_title = $websiteInfo->website_title;

        // get language
        $language = \App\Models\Language::query()->where('is_default', '=', 1)->first();

        // get service title
        $service = $order->service()->first();
        if ($service) {
            $arrData['serviceTitle'] = $service->content()->where('language_id', $language->id)->pluck('title')->first();
        } else {
            // Fallback for customer offer orders (no real service)
            $arrData['serviceTitle'] = $order->order_number ?? 'Custom Offer';
        }

        // get package title
        $package = $order->package()->first();
        if (is_null($package)) {
            $arrData['packageTitle'] = NULL;
        } else {
            $arrData['packageTitle'] = $package->name;
        }

        \PDF::loadView('frontend.service.invoice', $arrData)
            ->setPaper('a4')
            ->save(public_path($fileLocation));

        return $invoiceName;
    }

    /**
     * Update database with existing invoice filenames
     */
    private function updateInvoiceDatabase()
    {
        $this->info('Updating database with existing invoice filenames...');
        
        $orders = \App\Models\ClientService\ServiceOrder::all();
        $updated = 0;
        
        foreach ($orders as $order) {
            $invoicePath = public_path('assets/file/invoices/order-invoices/' . $order->order_number . '.pdf');
            
            if (file_exists($invoicePath) && (empty($order->invoice) || is_null($order->invoice))) {
                $order->update(['invoice' => $order->order_number . '.pdf']);
                $updated++;
                $this->info("Updated order #{$order->order_number} with invoice filename");
            }
        }
        
        $this->info("Updated {$updated} orders with invoice filenames!");
    }

    /**
     * Debug transactions to see why some show "No invoice"
     */
    private function debugTransactions()
    {
        $this->info('Debugging transactions...');
        
        $transactions = \App\Models\Transaction::where('transcation_type', 1)->get();
        
        foreach ($transactions as $transaction) {
            $order = $transaction->order()->first();
            
            if ($order) {
                $invoicePath = public_path('assets/file/invoices/order-invoices/' . $order->order_number . '.pdf');
                $fileExists = file_exists($invoicePath);
                
                $this->info("Transaction #{$transaction->transcation_id}:");
                $this->info("  - Order: #{$order->order_number} (ID: {$order->id})");
                $this->info("  - Invoice field: " . ($order->invoice ?: 'NULL'));
                $this->info("  - File exists: " . ($fileExists ? 'YES' : 'NO'));
                $this->info("  - File path: {$invoicePath}");
                $this->info("");
            } else {
                $this->warn("Transaction #{$transaction->transcation_id}: No associated order found!");
            }
        }
    }

    /**
     * Fix specific invoice filename issues
     */
    private function fixInvoiceFilenames()
    {
        $this->info('Fixing specific invoice filename issues...');
        
        // Fix the specific order that has wrong invoice filename
        $order = \App\Models\ClientService\ServiceOrder::where('order_number', '687b92ccd06ef')->first();
        if ($order) {
            $order->update(['invoice' => '687b92ccd06ef.pdf']);
            $this->info('Fixed invoice filename for order #687b92ccd06ef');
        }
        
        // Check if the file exists and rename if needed
        $wrongPath = public_path('assets/file/invoices/order-invoices/687b93ec732c0.pdf');
        $correctPath = public_path('assets/file/invoices/order-invoices/687b92ccd06ef.pdf');
        
        if (file_exists($wrongPath) && !file_exists($correctPath)) {
            rename($wrongPath, $correctPath);
            $this->info('Renamed invoice file from 687b93ec732c0.pdf to 687b92ccd06ef.pdf');
        }
        
        $this->info('Invoice filename fixes completed!');
    }

    /**
     * Move invoices from old service directory to new order-invoices directory
     */
    private function moveInvoices()
    {
        $this->info('Moving invoices from old directory to new directory...');
        
        $oldDirectory = public_path('assets/file/invoices/service/');
        $newDirectory = public_path('assets/file/invoices/order-invoices/');
        
        if (!file_exists($oldDirectory)) {
            $this->info('Old directory does not exist. Nothing to move.');
            return;
        }
        
        if (!file_exists($newDirectory)) {
            mkdir($newDirectory, 0775, true);
        }
        
        $files = glob($oldDirectory . '*.pdf');
        $moved = 0;
        
        foreach ($files as $file) {
            $filename = basename($file);
            $newPath = $newDirectory . $filename;
            
            if (!file_exists($newPath)) {
                rename($file, $newPath);
                $moved++;
                $this->info("Moved: {$filename}");
            } else {
                $this->info("File already exists in new directory: {$filename}");
            }
        }
        
        $this->info("Moved {$moved} invoice files!");
        
        // Update database for orders that have invoices in the old directory
        $orders = \App\Models\ClientService\ServiceOrder::whereNotNull('invoice')->get();
        $updated = 0;
        
        foreach ($orders as $order) {
            $oldPath = $oldDirectory . $order->invoice;
            $newPath = $newDirectory . $order->invoice;
            
            if (file_exists($newPath) && !file_exists($oldPath)) {
                // Invoice is already in the new directory, no need to update
                continue;
            }
            
            if (file_exists($oldPath)) {
                // Move the file
                rename($oldPath, $newPath);
                $updated++;
                $this->info("Moved and updated database for order: {$order->order_number}");
            }
        }
        
        $this->info("Updated {$updated} orders in database!");
    }

    /**
     * Check specific transactions by their IDs
     */
    private function checkSpecificTransactions()
    {
        $this->info('Checking specific transactions...');
        
        $transactionIds = ['687b9881c2777', '687b9873e32a0', '687b99620f7b2', '687b9c11b8671'];
        
        foreach ($transactionIds as $transactionId) {
            $transaction = \App\Models\Transaction::where('transcation_id', $transactionId)->first();
            
            if ($transaction) {
                $order = $transaction->order()->first();
                
                if ($order) {
                    $invoicePath = public_path('assets/file/invoices/order-invoices/' . $order->order_number . '.pdf');
                    $fileExists = file_exists($invoicePath);
                    
                    $this->info("Transaction #{$transactionId}:");
                    $this->info("  - Order: #{$order->order_number} (ID: {$order->id})");
                    $this->info("  - Invoice field: " . ($order->invoice ?: 'NULL'));
                    $this->info("  - File exists: " . ($fileExists ? 'YES' : 'NO'));
                    $this->info("  - File path: {$invoicePath}");
                    $this->info("");
                } else {
                    $this->warn("Transaction #{$transactionId}: No associated order found!");
                }
            } else {
                $this->warn("Transaction #{$transactionId}: Not found!");
            }
        }
    }

    /**
     * Fix all missing invoice database entries
     */
    private function fixAllInvoices()
    {
        $this->info('Fixing all missing invoice database entries...');
        
        $orders = \App\Models\ClientService\ServiceOrder::whereNotNull('order_number')->get();
        $fixed = 0;
        
        foreach ($orders as $order) {
            $invoicePath = public_path('assets/file/invoices/order-invoices/' . $order->order_number . '.pdf');
            
            if (file_exists($invoicePath) && (empty($order->invoice) || is_null($order->invoice))) {
                $order->update(['invoice' => $order->order_number . '.pdf']);
                $fixed++;
                $this->info("Fixed order #{$order->order_number}");
            }
        }
        
        $this->info("Fixed {$fixed} orders with missing invoice database entries!");
    }

    /**
     * Check a single specific transaction by ID
     */
    private function checkSingleTransaction($transactionId = null)
    {
        if (!$transactionId) {
            $transactionId = '687b9c11b8671'; // Default to the problematic one
        }
        
        $this->info("Checking transaction #{$transactionId}...");
        
        $transaction = \App\Models\Transaction::where('transcation_id', $transactionId)->first();
        
        if ($transaction) {
            $this->info("Transaction found:");
            $this->info("  - Order ID: " . ($transaction->order_id ?: 'NULL'));
            $this->info("  - Transaction Type: {$transaction->transcation_type}");
            
            $order = $transaction->order()->first();
            
            if ($order) {
                $invoicePath = public_path('assets/file/invoices/order-invoices/' . $order->order_number . '.pdf');
                $fileExists = file_exists($invoicePath);
                
                $this->info("  - Order: #{$order->order_number} (ID: {$order->id})");
                $this->info("  - Invoice field: " . ($order->invoice ?: 'NULL'));
                $this->info("  - File exists: " . ($fileExists ? 'YES' : 'NO'));
                $this->info("  - File path: {$invoicePath}");
                
                if ($fileExists && (empty($order->invoice) || is_null($order->invoice))) {
                    $this->info("  - FIXING: Updating database with invoice filename...");
                    $order->update(['invoice' => $order->order_number . '.pdf']);
                    $this->info("  - FIXED: Database updated!");
                }
            } else {
                $this->warn("  - No associated order found!");
                
                // Check if there's an order with this order_number that should be linked
                $order = \App\Models\ClientService\ServiceOrder::where('order_number', '687b9bf6a8160')->first();
                if ($order) {
                    $this->info("  - Found order #687b9bf6a8160 (ID: {$order->id}) that should be linked");
                    $this->info("  - FIXING: Updating transaction order_id...");
                    $transaction->update(['order_id' => $order->id]);
                    $this->info("  - FIXED: Transaction order_id updated!");
                }
            }
        } else {
            $this->warn("Transaction #{$transactionId}: Not found!");
        }
    }

    /**
     * Check and fix a specific order's invoice field
     */
    private function checkOrderInvoice($orderNumber = null)
    {
        if (!$orderNumber) {
            $orderNumber = '687b9bf6a8160'; // Default to the problematic one
        }
        
        $this->info("Checking order #{$orderNumber}...");
        
        $order = \App\Models\ClientService\ServiceOrder::where('order_number', $orderNumber)->first();
        
        if ($order) {
            $invoicePath = public_path('assets/file/invoices/order-invoices/' . $orderNumber . '.pdf');
            $fileExists = file_exists($invoicePath);
            
            $this->info("Order found:");
            $this->info("  - Order ID: {$order->id}");
            $this->info("  - Invoice field: " . ($order->invoice ?: 'NULL'));
            $this->info("  - File exists: " . ($fileExists ? 'YES' : 'NO'));
            $this->info("  - File path: {$invoicePath}");
            
            if ($fileExists && (empty($order->invoice) || is_null($order->invoice))) {
                $this->info("  - FIXING: Updating invoice field...");
                $order->update(['invoice' => $orderNumber . '.pdf']);
                $this->info("  - FIXED: Invoice field updated!");
            } else if (!$fileExists) {
                $this->warn("  - Invoice file does not exist!");
            } else {
                $this->info("  - Invoice field is already set correctly");
            }
        } else {
            $this->warn("Order #{$orderNumber}: Not found!");
        }
    }

    /**
     * Create a scheduled task for fixing missing invoices
     */
    private function createScheduledTask()
    {
        $this->info('Creating scheduled task for fixing missing invoices...');
        
        // This method will be called by the scheduler
        // You can add this to your app/Console/Kernel.php schedule method:
        // $schedule->command('test:email --fix-all-invoices')->everyMinute();
        
        $this->info('To enable automatic fixing, add this line to app/Console/Kernel.php in the schedule method:');
        $this->info('$schedule->command(\'test:email --fix-all-invoices\')->everyMinute();');
        $this->info('');
        $this->info('Or run this command manually whenever needed:');
        $this->info('php artisan test:email --fix-all-invoices');
    }

    /**
     * Check and fix missing membership invoices
     */
    private function checkMembershipInvoices()
    {
        $this->info('Checking for missing membership invoices...');
        
        // Check user memberships
        $userMemberships = \App\Models\UserMembership::whereNull('invoice')->get();
        $this->info("Found {$userMemberships->count()} user memberships without invoices");
        
        foreach ($userMemberships as $membership) {
            try {
                $invoiceName = $this->generateUserMembershipInvoice($membership);
                $membership->update(['invoice' => $invoiceName]);
                $this->info("Generated invoice for user membership #{$membership->id}: {$invoiceName}");
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for user membership #{$membership->id}: " . $e->getMessage());
            }
        }
        
        // Check seller memberships
        $sellerMemberships = \App\Models\Membership::whereNull('invoice')->get();
        $this->info("Found {$sellerMemberships->count()} seller memberships without invoices");
        
        foreach ($sellerMemberships as $membership) {
            try {
                $invoiceName = $this->generateSellerMembershipInvoice($membership);
                $membership->update(['invoice' => $invoiceName]);
                $this->info("Generated invoice for seller membership #{$membership->id}: {$invoiceName}");
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for seller membership #{$membership->id}: " . $e->getMessage());
            }
        }
        
        $this->info('Membership invoice check completed!');
    }
    
    /**
     * Generate user membership invoice
     */
    private function generateUserMembershipInvoice($membership)
    {
        $invoiceName = $membership->id . '_' . $membership->user_id . '_' . $membership->id . '.pdf';
        $directory = public_path('assets/file/invoices/user-memberships/');
        @mkdir($directory, 0775, true);
        
        $fileLocation = $directory . $invoiceName;
        
        // Get website info
        $bs = \App\Models\BasicSettings\Basic::first();
        
        // Get package info
        $package = \App\Models\UserPackage::find($membership->package_id);
        
        $data = [
            'user' => $membership->user,
            'membership' => $membership,
            'package' => $package,
            'bs' => $bs,
        ];
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('frontend.user.packages.invoice', $data);
        $pdf->save($fileLocation);
        
        return $invoiceName;
    }
    
    /**
     * Generate seller membership invoice
     */
    private function generateSellerMembershipInvoice($membership)
    {
        $invoiceName = $membership->id . '_' . $membership->seller_id . '_' . $membership->id . '.pdf';
        $directory = public_path('assets/file/invoices/seller-memberships/');
        @mkdir($directory, 0775, true);
        
        $fileLocation = $directory . $invoiceName;
        
        // Get website info
        $websiteInfo = \App\Models\BasicSettings\Basic::first();
        
        // Get package info
        $package = \App\Models\Package::find($membership->package_id);
        
        // Get seller info
        $seller = \App\Models\Seller::find($membership->seller_id);
        
        $data = [
            'websiteInfo' => $websiteInfo,
            'member' => $seller ? [
                'first_name' => $seller->first_name,
                'last_name' => $seller->last_name,
                'username' => $seller->username,
                'email' => $seller->email,
            ] : [],
            'package_title' => $package ? $package->title : 'Unknown Package',
            'amount' => $membership->price,
            'phone' => $seller ? $seller->phone : '',
            'order_id' => $membership->transaction_id,
            'request' => [
                'payment_method' => $membership->payment_method,
                'start_date' => $membership->start_date,
                'expire_date' => $membership->expire_date,
            ],
            'base_currency_text' => $websiteInfo->base_currency_text,
        ];
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.membership', $data);
        $pdf->save($fileLocation);
        
        return $invoiceName;
    }
    
    /**
     * Check a specific transaction by ID
     */
    private function checkSpecificTransaction($transactionId)
    {
        $this->info("Checking transaction: {$transactionId}");
        
        $transaction = \App\Models\Transaction::where('transcation_id', $transactionId)->first();
        
        if (!$transaction) {
            $this->error("Transaction not found!");
            return;
        }
        
        $this->info("Transaction found:");
        $this->info("- Order ID: {$transaction->order_id}");
        $this->info("- Type: {$transaction->transcation_type}");
        $this->info("- User ID: " . ($transaction->user_id ?? 'null'));
        $this->info("- Seller ID: " . ($transaction->seller_id ?? 'null'));
        $this->info("- Payment Method: {$transaction->payment_method}");
        $this->info("- Payment Status: {$transaction->payment_status}");
        $this->info("- Created: {$transaction->created_at}");
        
        if ($transaction->transcation_type == 1) {
            // Service order
            $this->info("This is a Service Order transaction");
            $order = \App\Models\ClientService\ServiceOrder::find($transaction->order_id);
            if ($order) {
                $this->info("Service Order found:");
                $this->info("- Invoice: " . ($order->invoice ?? 'null'));
                $this->info("- Status: {$order->status}");
            } else {
                $this->error("Service Order not found!");
            }
        } elseif ($transaction->transcation_type == 5) {
            // Package purchase
            $this->info("This is a Package Purchase transaction");
            
            if ($transaction->user_id) {
                // User membership
                $this->info("Checking for User Membership...");
                $membership = \App\Models\UserMembership::find($transaction->order_id);
                if ($membership) {
                    $this->info("User Membership found:");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    $this->info("- Payment Method: {$membership->payment_method}");
                } else {
                    $this->error("User Membership not found!");
                }
            } elseif ($transaction->seller_id) {
                // Seller membership
                $this->info("Checking for Seller Membership...");
                $membership = \App\Models\Membership::find($transaction->order_id);
                if ($membership) {
                    $this->info("Seller Membership found:");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    $this->info("- Payment Method: {$membership->payment_method}");
                    
                    // Check if invoice file exists
                    if ($membership->invoice) {
                        $filePath = public_path('assets/file/invoices/seller-memberships/' . $membership->invoice);
                        $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        $this->info("- Invoice file path: {$filePath}");
                    }
                } else {
                    $this->error("Seller Membership not found!");
                    
                    // Try to find an existing membership to link to
                    $this->info("Looking for existing seller membership to link to...");
                    $existingMembership = \App\Models\Membership::orderBy('created_at', 'desc')->first();
                    if ($existingMembership) {
                        $this->info("Found existing membership (ID: {$existingMembership->id}) to link to.");
                        $transaction->update(['order_id' => $existingMembership->id, 'seller_id' => $existingMembership->seller_id]);
                        $this->info("FIXED: Updated transaction order_id to {$existingMembership->id} and seller_id to {$existingMembership->seller_id}");
                        
                        // Check if invoice exists
                        if ($existingMembership->invoice) {
                            $filePath = public_path('assets/file/invoices/seller-memberships/' . $existingMembership->invoice);
                            $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        } else {
                            $this->info("Membership has no invoice - generating one...");
                            try {
                                $invoiceName = $this->generateSellerMembershipInvoice($existingMembership);
                                $existingMembership->update(['invoice' => $invoiceName]);
                                $this->info("Generated invoice: {$invoiceName}");
                            } catch (\Exception $e) {
                                $this->error("Failed to generate invoice: " . $e->getMessage());
                            }
                        }
                    } else {
                        $this->error("No existing seller memberships found!");
                    }
                }
            } else {
                // No user_id or seller_id - this is the problem!
                $this->error("Package Purchase transaction has no user_id or seller_id!");
                $this->info("This transaction was created incorrectly.");
                
                // Try to find the membership by order_id anyway
                $this->info("Searching for membership with ID {$transaction->order_id}...");
                $membership = \App\Models\Membership::find($transaction->order_id);
                if ($membership) {
                    $this->info("Found membership with ID {$transaction->order_id}:");
                    $this->info("- Seller ID: {$membership->seller_id}");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    
                    // Fix the transaction
                    $transaction->update(['seller_id' => $membership->seller_id]);
                    $this->info("FIXED: Updated transaction seller_id to {$membership->seller_id}");
                    
                    // Check if invoice exists
                    if ($membership->invoice) {
                        $filePath = public_path('assets/file/invoices/seller-memberships/' . $membership->invoice);
                        $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                    } else {
                        $this->info("Membership has no invoice - generating one...");
                        try {
                            $invoiceName = $this->generateSellerMembershipInvoice($membership);
                            $membership->update(['invoice' => $invoiceName]);
                            $this->info("Generated invoice: {$invoiceName}");
                        } catch (\Exception $e) {
                            $this->error("Failed to generate invoice: " . $e->getMessage());
                        }
                    }
                } else {
                    $this->error("No membership found with ID {$transaction->order_id}");
                    
                    // Also check for user membership
                    $this->info("Checking for User Membership with ID {$transaction->order_id}...");
                    $userMembership = \App\Models\UserMembership::find($transaction->order_id);
                    if ($userMembership) {
                        $this->info("Found User Membership with ID {$transaction->order_id}:");
                        $this->info("- User ID: {$userMembership->user_id}");
                        $this->info("- Invoice: " . ($userMembership->invoice ?? 'null'));
                        $this->info("- Status: {$userMembership->status}");
                        
                        // Fix the transaction
                        $transaction->update(['user_id' => $userMembership->user_id]);
                        $this->info("FIXED: Updated transaction user_id to {$userMembership->user_id}");
                        
                        // Check if invoice exists
                        if ($userMembership->invoice) {
                            $filePath = public_path('assets/file/invoices/user-memberships/' . $userMembership->invoice);
                            $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        } else {
                            $this->info("User Membership has no invoice - generating one...");
                            try {
                                $invoiceName = $this->generateUserMembershipInvoice($userMembership);
                                $userMembership->update(['invoice' => $invoiceName]);
                                $this->info("Generated invoice: {$invoiceName}");
                            } catch (\Exception $e) {
                                $this->error("Failed to generate invoice: " . $e->getMessage());
                            }
                        }
                    } else {
                        $this->error("No User Membership found with ID {$transaction->order_id}");
                    }
                }
            }
        } else {
            $this->info("Unknown transaction type: {$transaction->transcation_type}");
        }
        
        $this->info("Transaction check completed!");
    }

    /**
     * Fix transactions with missing seller_id
     */
    private function fixMissingSellerId()
    {
        $this->info('Fixing transactions with missing seller_id...');
        
        // Fix seller membership transactions
        $membershipTransactions = \App\Models\Transaction::where('transcation_type', 5)
            ->whereNull('seller_id')
            ->whereNull('user_id')
            ->get();
            
        $fixed = 0;
        
        foreach ($membershipTransactions as $transaction) {
            $membership = \App\Models\Membership::find($transaction->order_id);
            
            if ($membership && $membership->seller_id) {
                $this->info("Transaction #{$transaction->transcation_id}:");
                $this->info("  - Membership ID: {$membership->id}");
                $this->info("  - Current seller_id: " . ($transaction->seller_id ?? 'NULL'));
                $this->info("  - Membership seller_id: {$membership->seller_id}");
                
                $transaction->update(['seller_id' => $membership->seller_id]);
                $this->info("  - FIXED: seller_id updated to {$membership->seller_id}");
                $fixed++;
            } else {
                $this->warn("Transaction #{$transaction->transcation_id}: No valid membership found!");
            }
        }
        
        $this->info("Fixed {$fixed} seller membership transactions with missing seller_id!");
    }

    /**
     * Fix all transactions with missing seller_id or user_id
     */
    private function fixAllTransactions()
    {
        $this->info('Fixing all transactions with missing seller_id or user_id...');

        // Fix user membership transactions
        $userMembershipTransactions = \App\Models\Transaction::where('transcation_type', 5)
            ->whereNull('user_id')
            ->get();

        $userFixed = 0;
        foreach ($userMembershipTransactions as $transaction) {
            $userMembership = \App\Models\UserMembership::find($transaction->order_id);
            if ($userMembership && $userMembership->user_id) {
                $transaction->update(['user_id' => $userMembership->user_id]);
                $this->info("Fixed user membership transaction #{$transaction->transcation_id}: user_id updated to {$userMembership->user_id}");
                $userFixed++;
            } else {
                $this->warn("User membership transaction #{$transaction->transcation_id}: No valid user membership found or user_id already set.");
            }
        }
        $this->info("Fixed {$userFixed} user membership transactions with missing user_id!");

        // Fix seller membership transactions
        $sellerMembershipTransactions = \App\Models\Transaction::where('transcation_type', 5)
            ->whereNull('seller_id')
            ->get();

        $sellerFixed = 0;
        foreach ($sellerMembershipTransactions as $transaction) {
            $sellerMembership = \App\Models\Membership::find($transaction->order_id);
            if ($sellerMembership && $sellerMembership->seller_id) {
                $transaction->update(['seller_id' => $sellerMembership->seller_id]);
                $this->info("Fixed seller membership transaction #{$transaction->transcation_id}: seller_id updated to {$sellerMembership->seller_id}");
                $sellerFixed++;
            } else {
                $this->warn("Seller membership transaction #{$transaction->transcation_id}: No valid seller membership found or seller_id already set.");
            }
        }
        $this->info("Fixed {$sellerFixed} seller membership transactions with missing seller_id!");

        $this->info("All transactions with missing seller_id or user_id have been checked and fixed.");
    }

    /**
     * List all user and seller memberships with their IDs
     */
    private function listMemberships()
    {
        $this->info('Listing all user and seller memberships with their IDs...');

        try {
            $userMemberships = \App\Models\UserMembership::withTrashed()->get();
            $this->info("Found " . $userMemberships->count() . " User Memberships (including deleted):");
            foreach ($userMemberships as $membership) {
                $deletedStatus = $membership->deleted_at ? ' (DELETED)' : '';
                $this->line("ID: {$membership->id}, User ID: {$membership->user_id}, Package ID: {$membership->package_id}, Status: {$membership->status}, Invoice: " . ($membership->invoice ?? 'null') . $deletedStatus);
            }
        } catch (\Exception $e) {
            $this->error("Error getting user memberships: " . $e->getMessage());
        }

        try {
            $sellerMemberships = \App\Models\Membership::withTrashed()->get();
            $this->info("Found " . $sellerMemberships->count() . " Seller Memberships (including deleted):");
            foreach ($sellerMemberships as $membership) {
                $deletedStatus = $membership->deleted_at ? ' (DELETED)' : '';
                $this->line("ID: {$membership->id}, Seller ID: {$membership->seller_id}, Package ID: {$membership->package_id}, Status: {$membership->status}, Invoice: " . ($membership->invoice ?? 'null') . $deletedStatus);
            }
        } catch (\Exception $e) {
            $this->error("Error getting seller memberships: " . $e->getMessage());
        }

        $this->info('Membership listing completed!');
    }
    
    /**
     * Fix payment controllers to not delete invoice files and add payment method fallbacks
     */
    private function fixPaymentControllers()
    {
        $this->info('Fixing payment controllers to not delete invoice files and add payment method fallbacks...');
        
        $paymentControllers = [
            'app/Http/Controllers/Payment/StripeController.php',
            'app/Http/Controllers/Payment/PaypalController.php',
            'app/Http/Controllers/Payment/PaystackController.php',
            'app/Http/Controllers/Payment/RazorpayController.php',
            'app/Http/Controllers/Payment/PaytmController.php',
            'app/Http/Controllers/Payment/MollieController.php',
            'app/Http/Controllers/Payment/MidtransController.php',
            'app/Http/Controllers/Payment/MercadopagoController.php',
            'app/Http/Controllers/Payment/FlutterWaveController.php',
            'app/Http/Controllers/Payment/InstamojoController.php',
            'app/Http/Controllers/Payment/IyzicoController.php',
            'app/Http/Controllers/Payment/MyFatoorahController.php',
            'app/Http/Controllers/Payment/XenditController.php',
            'app/Http/Controllers/Payment/AuthorizeController.php',
            'app/Http/Controllers/Payment/PerfectMoneyController.php',
            'app/Http/Controllers/Payment/PaytabsController.php',
            'app/Http/Controllers/Payment/PhonePeController.php',
            'app/Http/Controllers/Payment/ToyyibpayController.php',
            'app/Http/Controllers/Payment/YocoController.php',
        ];
        
        $fixed = 0;
        
        foreach ($paymentControllers as $controllerPath) {
            if (file_exists($controllerPath)) {
                $content = file_get_contents($controllerPath);
                $originalContent = $content;
                
                // Remove @unlink calls for invoice files
                $content = preg_replace('/@unlink\(public_path\(\'assets\/front\/invoices\/\' \. \$file_name\)\);\s*\n/', '', $content);
                
                // Update makeInvoice calls to use correct folder for seller memberships
                $content = preg_replace('/\$this->makeInvoice\(([^,]+), "membership", ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^)]+)\)/', '$this->makeInvoice($1, "membership", $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, "seller-memberships")', $content);
                
                // Update makeInvoice calls to use correct folder for extensions
                $content = preg_replace('/\$this->makeInvoice\(([^,]+), "extend", ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^,]+), ([^)]+)\)/', '$this->makeInvoice($1, "extend", $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, "seller-memberships")', $content);
                
                // Add payment method fallbacks
                $controllerName = basename($controllerPath, '.php');
                $paymentMethod = str_replace('Controller', '', $controllerName);
                
                // Map controller names to proper payment method names
                $methodMap = [
                    'StripeController' => 'Stripe',
                    'PaypalController' => 'PayPal',
                    'PaystackController' => 'Paystack',
                    'RazorpayController' => 'Razorpay',
                    'PaytmController' => 'Paytm',
                    'MollieController' => 'Mollie',
                    'MidtransController' => 'Midtrans',
                    'MercadopagoController' => 'MercadoPago',
                    'FlutterWaveController' => 'Flutterwave',
                    'InstamojoController' => 'Instamojo',
                    'IyzicoController' => 'Iyzico',
                    'MyFatoorahController' => 'Myfatoorah',
                    'XenditController' => 'Xendit',
                    'AuthorizeController' => 'Authorize.Net',
                    'PerfectMoneyController' => 'Perfect Money',
                    'PaytabsController' => 'Paytabs',
                    'PhonePeController' => 'PhonePe',
                    'ToyyibpayController' => 'Toyyibpay',
                    'YocoController' => 'Yoco',
                ];
                
                $properMethodName = $methodMap[$controllerName] ?? $paymentMethod;
                
                // Fix payment method fallbacks
                $content = preg_replace('/\$transaction_data\[\'payment_method\'\] = \$lastMemb->payment_method;/', "\$transaction_data['payment_method'] = \$lastMemb->payment_method ?: '{$properMethodName}';", $content);
                
                if ($content !== $originalContent) {
                    file_put_contents($controllerPath, $content);
                    $this->info("Fixed: {$controllerPath}");
                    $fixed++;
                } else {
                    $this->info("No changes needed: {$controllerPath}");
                }
            } else {
                $this->warn("File not found: {$controllerPath}");
            }
        }
        
        $this->info("Fixed {$fixed} payment controllers!");
        $this->info("Now seller membership invoices will be saved to the database and not deleted after email sending.");
    }

    /**
     * Test automatic invoice generation for new seller memberships
     */
    private function testAutomaticInvoices()
    {
        $this->info('Testing automatic invoice generation for new seller memberships...');
        
        // Simulate a new seller membership being created
        $newMembership = new \App\Models\Membership();
        $newMembership->user_id = 1; // Example user ID
        $newMembership->seller_id = 1; // Example seller ID
        $newMembership->package_id = 1; // Example package ID
        $newMembership->price = 100;
        $newMembership->start_date = now();
        $newMembership->expire_date = now()->addYear();
        $newMembership->payment_method = 'test';
        $newMembership->payment_status = 'completed';
        $newMembership->transaction_id = 123; // Example transaction ID
        $newMembership->status = 'active';
        $newMembership->save();

        $this->info('New seller membership created with ID: ' . $newMembership->id);

        // Check if an invoice was generated and saved
        $generatedInvoice = \App\Models\Membership::find($newMembership->id);

        if ($generatedInvoice && $generatedInvoice->invoice) {
            $this->info('Automatic invoice generation successful!');
            $this->info('Generated Invoice: ' . $generatedInvoice->invoice);
            $this->info('Invoice path: ' . public_path('assets/file/invoices/seller-memberships/' . $generatedInvoice->invoice));
        } else {
            $this->error('Automatic invoice generation failed or invoice not found.');
            $this->error('Generated Invoice: ' . ($generatedInvoice ? $generatedInvoice->invoice : 'NULL'));
        }
    }

    /**
     * Fix transactions with non-existent order_ids by linking them to existing memberships.
     */
    private function fixOrphanedTransactions()
    {
        $this->info('Fixing transactions with non-existent order_ids...');

        // Find transactions where order_id exists but membership doesn't
        $transactions = \App\Models\Transaction::where('transcation_type', 5)->get();
        $fixed = 0;

        foreach ($transactions as $transaction) {
            $this->info("Checking transaction #{$transaction->transcation_id} (Order ID: {$transaction->order_id})...");

            // Check if the membership exists
            $userMembership = \App\Models\UserMembership::find($transaction->order_id);
            $sellerMembership = \App\Models\Membership::find($transaction->order_id);

            if (!$userMembership && !$sellerMembership) {
                $this->warn("Membership with ID {$transaction->order_id} does not exist. Looking for alternative...");

                // Try to find a membership with the same transaction_id
                $userMembershipByTransactionId = \App\Models\UserMembership::where('transaction_id', $transaction->order_id)->first();
                $sellerMembershipByTransactionId = \App\Models\Membership::where('transaction_id', $transaction->order_id)->first();

                if ($userMembershipByTransactionId) {
                    $this->info("Found User Membership with transaction_id {$transaction->order_id} (ID: {$userMembershipByTransactionId->id}). Linking transaction.");
                    $transaction->update(['order_id' => $userMembershipByTransactionId->id]);
                    $fixed++;
                } elseif ($sellerMembershipByTransactionId) {
                    $this->info("Found Seller Membership with transaction_id {$transaction->order_id} (ID: {$sellerMembershipByTransactionId->id}). Linking transaction.");
                    $transaction->update(['order_id' => $sellerMembershipByTransactionId->id]);
                    $fixed++;
                } else {
                    // If no membership found by transaction_id, try to find the most recent membership
                    $recentUserMembership = \App\Models\UserMembership::orderBy('created_at', 'desc')->first();
                    $recentSellerMembership = \App\Models\Membership::orderBy('created_at', 'desc')->first();

                    if ($recentSellerMembership) {
                        $this->info("Linking to most recent seller membership (ID: {$recentSellerMembership->id}).");
                        $transaction->update(['order_id' => $recentSellerMembership->id]);
                        $fixed++;
                    } elseif ($recentUserMembership) {
                        $this->info("Linking to most recent user membership (ID: {$recentUserMembership->id}).");
                        $transaction->update(['order_id' => $recentUserMembership->id]);
                        $fixed++;
                    } else {
                        $this->error("No memberships found to link transaction #{$transaction->transcation_id}.");
                    }
                }
            } else {
                $this->info("Membership with ID {$transaction->order_id} exists. No fix needed.");
            }
        }

        $this->info("Fixed {$fixed} transactions with non-existent order_ids!");
    }

    /**
     * Fix a specific transaction by linking it to an existing membership.
     */
    private function fixSpecificTransaction($transactionId)
    {
        $this->info("Attempting to fix transaction #{$transactionId}...");

        $transaction = \App\Models\Transaction::where('transcation_id', $transactionId)->first();

        if (!$transaction) {
            $this->error("Transaction #{$transactionId}: Not found!");
            return;
        }

        $this->info("Transaction found:");
        $this->info("- Order ID: {$transaction->order_id}");
        $this->info("- Type: {$transaction->transcation_type}");
        $this->info("- User ID: " . ($transaction->user_id ?? 'null'));
        $this->info("- Seller ID: " . ($transaction->seller_id ?? 'null'));
        $this->info("- Payment Method: {$transaction->payment_method}");
        $this->info("- Payment Status: {$transaction->payment_status}");
        $this->info("- Created: {$transaction->created_at}");

        if ($transaction->transcation_type == 1) {
            // Service order
            $this->info("This is a Service Order transaction");
            $order = \App\Models\ClientService\ServiceOrder::find($transaction->order_id);
            if ($order) {
                $this->info("Service Order found:");
                $this->info("- Invoice: " . ($order->invoice ?? 'null'));
                $this->info("- Status: {$order->status}");
            } else {
                $this->error("Service Order not found!");
            }
        } elseif ($transaction->transcation_type == 5) {
            // Package purchase
            $this->info("This is a Package Purchase transaction");
            
            if ($transaction->user_id) {
                // User membership
                $this->info("Checking for User Membership...");
                $membership = \App\Models\UserMembership::find($transaction->order_id);
                if ($membership) {
                    $this->info("User Membership found:");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    $this->info("- Payment Method: {$membership->payment_method}");
                } else {
                    $this->error("User Membership not found!");
                }
            } elseif ($transaction->seller_id) {
                // Seller membership
                $this->info("Checking for Seller Membership...");
                $membership = \App\Models\Membership::find($transaction->order_id);
                if ($membership) {
                    $this->info("Seller Membership found:");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    $this->info("- Payment Method: {$membership->payment_method}");
                    
                    // Check if invoice file exists
                    if ($membership->invoice) {
                        $filePath = public_path('assets/file/invoices/seller-memberships/' . $membership->invoice);
                        $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        $this->info("- Invoice file path: {$filePath}");
                    }
                } else {
                    $this->error("Seller Membership not found!");
                    
                    // Try to find an existing membership to link to
                    $this->info("Looking for existing seller membership to link to...");
                    $existingMembership = \App\Models\Membership::orderBy('created_at', 'desc')->first();
                    if ($existingMembership) {
                        $this->info("Found existing membership (ID: {$existingMembership->id}) to link to.");
                        $transaction->update(['order_id' => $existingMembership->id, 'seller_id' => $existingMembership->seller_id]);
                        $this->info("FIXED: Updated transaction order_id to {$existingMembership->id} and seller_id to {$existingMembership->seller_id}");
                        
                        // Check if invoice exists
                        if ($existingMembership->invoice) {
                            $filePath = public_path('assets/file/invoices/seller-memberships/' . $existingMembership->invoice);
                            $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        } else {
                            $this->info("Membership has no invoice - generating one...");
                            try {
                                $invoiceName = $this->generateSellerMembershipInvoice($existingMembership);
                                $existingMembership->update(['invoice' => $invoiceName]);
                                $this->info("Generated invoice: {$invoiceName}");
                            } catch (\Exception $e) {
                                $this->error("Failed to generate invoice: " . $e->getMessage());
                            }
                        }
                    } else {
                        $this->error("No existing seller memberships found!");
                    }
                }
            } else {
                // No user_id or seller_id - this is the problem!
                $this->error("Package Purchase transaction has no user_id or seller_id!");
                $this->info("This transaction was created incorrectly.");
                
                // Try to find the membership by order_id anyway
                $this->info("Searching for membership with ID {$transaction->order_id}...");
                $membership = \App\Models\Membership::find($transaction->order_id);
                if ($membership) {
                    $this->info("Found membership with ID {$transaction->order_id}:");
                    $this->info("- Seller ID: {$membership->seller_id}");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    
                    // Fix the transaction
                    $transaction->update(['seller_id' => $membership->seller_id]);
                    $this->info("FIXED: Updated transaction seller_id to {$membership->seller_id}");
                    
                    // Check if invoice exists
                    if ($membership->invoice) {
                        $filePath = public_path('assets/file/invoices/seller-memberships/' . $membership->invoice);
                        $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                    } else {
                        $this->info("Membership has no invoice - generating one...");
                        try {
                            $invoiceName = $this->generateSellerMembershipInvoice($membership);
                            $membership->update(['invoice' => $invoiceName]);
                            $this->info("Generated invoice: {$invoiceName}");
                        } catch (\Exception $e) {
                            $this->error("Failed to generate invoice: " . $e->getMessage());
                        }
                    }
                } else {
                    $this->error("No membership found with ID {$transaction->order_id}");
                    
                    // Also check for user membership
                    $this->info("Checking for User Membership with ID {$transaction->order_id}...");
                    $userMembership = \App\Models\UserMembership::find($transaction->order_id);
                    if ($userMembership) {
                        $this->info("Found User Membership with ID {$transaction->order_id}:");
                        $this->info("- User ID: {$userMembership->user_id}");
                        $this->info("- Invoice: " . ($userMembership->invoice ?? 'null'));
                        $this->info("- Status: {$userMembership->status}");
                        
                        // Fix the transaction
                        $transaction->update(['user_id' => $userMembership->user_id]);
                        $this->info("FIXED: Updated transaction user_id to {$userMembership->user_id}");
                        
                        // Check if invoice exists
                        if ($userMembership->invoice) {
                            $filePath = public_path('assets/file/invoices/user-memberships/' . $userMembership->invoice);
                            $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        } else {
                            $this->info("User Membership has no invoice - generating one...");
                            try {
                                $invoiceName = $this->generateUserMembershipInvoice($userMembership);
                                $userMembership->update(['invoice' => $invoiceName]);
                                $this->info("Generated invoice: {$invoiceName}");
                            } catch (\Exception $e) {
                                $this->error("Failed to generate invoice: " . $e->getMessage());
                            }
                        }
                    } else {
                        $this->error("No User Membership found with ID {$transaction->order_id}");
                    }
                }
            }
        } else {
            $this->info("Unknown transaction type: {$transaction->transcation_type}");
        }
        
        $this->info("Transaction check completed!");
    }

    /**
     * Quick fix a specific transaction by its ID.
     */
    private function quickFixTransaction()
    {
        $transactionId = $this->argument('email');
        if (!$transactionId) {
            $this->error('Transaction ID is required for --quick-fix-transaction option.');
            return 1;
        }

        $this->info("Attempting to quick fix transaction #{$transactionId}...");

        $transaction = \App\Models\Transaction::where('transcation_id', $transactionId)->first();

        if (!$transaction) {
            $this->error("Transaction #{$transactionId}: Not found!");
            return 1;
        }

        $this->info("Transaction found:");
        $this->info("- Order ID: {$transaction->order_id}");
        $this->info("- Type: {$transaction->transcation_type}");
        $this->info("- User ID: " . ($transaction->user_id ?? 'null'));
        $this->info("- Seller ID: " . ($transaction->seller_id ?? 'null'));
        $this->info("- Payment Method: {$transaction->payment_method}");
        $this->info("- Payment Status: {$transaction->payment_status}");
        $this->info("- Created: {$transaction->created_at}");

        if ($transaction->transcation_type == 1) {
            // Service order
            $this->info("This is a Service Order transaction");
            $order = \App\Models\ClientService\ServiceOrder::find($transaction->order_id);
            if ($order) {
                $this->info("Service Order found:");
                $this->info("- Invoice: " . ($order->invoice ?? 'null'));
                $this->info("- Status: {$order->status}");
            } else {
                $this->error("Service Order not found!");
            }
        } elseif ($transaction->transcation_type == 5) {
            // Package purchase
            $this->info("This is a Package Purchase transaction");
            
            if ($transaction->user_id) {
                // User membership
                $this->info("Checking for User Membership...");
                $membership = \App\Models\UserMembership::find($transaction->order_id);
                if ($membership) {
                    $this->info("User Membership found:");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    $this->info("- Payment Method: {$membership->payment_method}");
                } else {
                    $this->error("User Membership not found!");
                }
            } elseif ($transaction->seller_id) {
                // Seller membership
                $this->info("Checking for Seller Membership...");
                $membership = \App\Models\Membership::find($transaction->order_id);
                if ($membership) {
                    $this->info("Seller Membership found:");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    $this->info("- Payment Method: {$membership->payment_method}");
                    
                    // Check if invoice file exists
                    if ($membership->invoice) {
                        $filePath = public_path('assets/file/invoices/seller-memberships/' . $membership->invoice);
                        $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        $this->info("- Invoice file path: {$filePath}");
                    }
                } else {
                    $this->error("Seller Membership not found!");
                    
                    // Try to find an existing membership to link to
                    $this->info("Looking for existing seller membership to link to...");
                    $existingMembership = \App\Models\Membership::orderBy('created_at', 'desc')->first();
                    if ($existingMembership) {
                        $this->info("Found existing membership (ID: {$existingMembership->id}) to link to.");
                        $transaction->update(['order_id' => $existingMembership->id, 'seller_id' => $existingMembership->seller_id]);
                        $this->info("FIXED: Updated transaction order_id to {$existingMembership->id} and seller_id to {$existingMembership->seller_id}");
                        
                        // Check if invoice exists
                        if ($existingMembership->invoice) {
                            $filePath = public_path('assets/file/invoices/seller-memberships/' . $existingMembership->invoice);
                            $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        } else {
                            $this->info("Membership has no invoice - generating one...");
                            try {
                                $invoiceName = $this->generateSellerMembershipInvoice($existingMembership);
                                $existingMembership->update(['invoice' => $invoiceName]);
                                $this->info("Generated invoice: {$invoiceName}");
                            } catch (\Exception $e) {
                                $this->error("Failed to generate invoice: " . $e->getMessage());
                            }
                        }
                    } else {
                        $this->error("No existing seller memberships found!");
                    }
                }
            } else {
                // No user_id or seller_id - this is the problem!
                $this->error("Package Purchase transaction has no user_id or seller_id!");
                $this->info("This transaction was created incorrectly.");
                
                // Try to find the membership by order_id anyway
                $this->info("Searching for membership with ID {$transaction->order_id}...");
                $membership = \App\Models\Membership::find($transaction->order_id);
                if ($membership) {
                    $this->info("Found membership with ID {$transaction->order_id}:");
                    $this->info("- Seller ID: {$membership->seller_id}");
                    $this->info("- Invoice: " . ($membership->invoice ?? 'null'));
                    $this->info("- Status: {$membership->status}");
                    
                    // Fix the transaction
                    $transaction->update(['seller_id' => $membership->seller_id]);
                    $this->info("FIXED: Updated transaction seller_id to {$membership->seller_id}");
                    
                    // Check if invoice exists
                    if ($membership->invoice) {
                        $filePath = public_path('assets/file/invoices/seller-memberships/' . $membership->invoice);
                        $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                    } else {
                        $this->info("Membership has no invoice - generating one...");
                        try {
                            $invoiceName = $this->generateSellerMembershipInvoice($membership);
                            $membership->update(['invoice' => $invoiceName]);
                            $this->info("Generated invoice: {$invoiceName}");
                        } catch (\Exception $e) {
                            $this->error("Failed to generate invoice: " . $e->getMessage());
                        }
                    }
                } else {
                    $this->error("No membership found with ID {$transaction->order_id}");
                    
                    // Also check for user membership
                    $this->info("Checking for User Membership with ID {$transaction->order_id}...");
                    $userMembership = \App\Models\UserMembership::find($transaction->order_id);
                    if ($userMembership) {
                        $this->info("Found User Membership with ID {$transaction->order_id}:");
                        $this->info("- User ID: {$userMembership->user_id}");
                        $this->info("- Invoice: " . ($userMembership->invoice ?? 'null'));
                        $this->info("- Status: {$userMembership->status}");
                        
                        // Fix the transaction
                        $transaction->update(['user_id' => $userMembership->user_id]);
                        $this->info("FIXED: Updated transaction user_id to {$userMembership->user_id}");
                        
                        // Check if invoice exists
                        if ($userMembership->invoice) {
                            $filePath = public_path('assets/file/invoices/user-memberships/' . $userMembership->invoice);
                            $this->info("- Invoice file exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
                        } else {
                            $this->info("User Membership has no invoice - generating one...");
                            try {
                                $invoiceName = $this->generateUserMembershipInvoice($userMembership);
                                $userMembership->update(['invoice' => $invoiceName]);
                                $this->info("Generated invoice: {$invoiceName}");
                            } catch (\Exception $e) {
                                $this->error("Failed to generate invoice: " . $e->getMessage());
                            }
                        }
                    } else {
                        $this->error("No User Membership found with ID {$transaction->order_id}");
                    }
                }
            }
        } else {
            $this->info("Unknown transaction type: {$transaction->transcation_type}");
        }
        
        $this->info("Transaction check completed!");
        return 0;
    }

    /**
     * Debug all memberships and their invoice files for mismatches.
     */
    private function debugInvoices()
    {
        $this->info('Debugging all memberships and their invoice files...');

        try {
            $userMemberships = \App\Models\UserMembership::all();
            $this->info("Found " . $userMemberships->count() . " User Memberships:");
            foreach ($userMemberships as $membership) {
                $this->line("ID: {$membership->id}, User ID: {$membership->user_id}, Package ID: {$membership->package_id}, Status: {$membership->status}, Invoice: " . ($membership->invoice ?? 'null'));
                if ($membership->invoice) {
                    $filePath = public_path('assets/file/invoices/user-memberships/' . $membership->invoice);
                    $this->line("  - Invoice File: " . (file_exists($filePath) ? 'Exists' : 'Does NOT Exist'));
                    $this->line("  - File Path: {$filePath}");
                } else {
                    $this->line("  - Invoice File: Does NOT Exist");
                }
            }
        } catch (\Exception $e) {
            $this->error("Error getting user memberships: " . $e->getMessage());
        }

        try {
            $sellerMemberships = \App\Models\Membership::all();
            $this->info("Found " . $sellerMemberships->count() . " Seller Memberships:");
            foreach ($sellerMemberships as $membership) {
                $this->line("ID: {$membership->id}, Seller ID: {$membership->seller_id}, Package ID: {$membership->package_id}, Status: {$membership->status}, Invoice: " . ($membership->invoice ?? 'null'));
                if ($membership->invoice) {
                    $filePath = public_path('assets/file/invoices/seller-memberships/' . $membership->invoice);
                    $this->line("  - Invoice File: " . (file_exists($filePath) ? 'Exists' : 'Does NOT Exist'));
                    $this->line("  - File Path: {$filePath}");
                } else {
                    $this->line("  - Invoice File: Does NOT Exist");
                }
            }
        } catch (\Exception $e) {
            $this->error("Error getting seller memberships: " . $e->getMessage());
        }

        $this->info('Membership invoice debug completed!');
    }

    /**
     * Generate missing invoice files for all memberships.
     */
    private function generateMissingInvoices()
    {
        $this->info('Generating missing invoice files for all memberships...');

        try {
            $userMemberships = \App\Models\UserMembership::whereNull('invoice')->get();
            $this->info("Found {$userMemberships->count()} user memberships without invoices.");
            foreach ($userMemberships as $membership) {
                try {
                    $invoiceName = $this->generateUserMembershipInvoice($membership);
                    $membership->update(['invoice' => $invoiceName]);
                    $this->info("Generated invoice for user membership #{$membership->id}: {$invoiceName}");
                } catch (\Exception $e) {
                    $this->error("Failed to generate invoice for user membership #{$membership->id}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->error("Error getting user memberships for invoice generation: " . $e->getMessage());
        }

        try {
            $sellerMemberships = \App\Models\Membership::whereNull('invoice')->get();
            $this->info("Found {$sellerMemberships->count()} seller memberships without invoices.");
            foreach ($sellerMemberships as $membership) {
                try {
                    $invoiceName = $this->generateSellerMembershipInvoice($membership);
                    $membership->update(['invoice' => $invoiceName]);
                    $this->info("Generated invoice for seller membership #{$membership->id}: {$invoiceName}");
                } catch (\Exception $e) {
                    $this->error("Failed to generate invoice for seller membership #{$membership->id}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->error("Error getting seller memberships for invoice generation: " . $e->getMessage());
        }

        $this->info('All missing invoice generation completed!');
    }

    /**
     * Fix a specific membership invoice by regenerating it.
     */
    private function fixMembershipInvoice($membershipId)
    {
        $this->info("Attempting to fix membership invoice for ID: {$membershipId}...");

        $membership = \App\Models\Membership::find($membershipId);

        if (!$membership) {
            $this->error("Membership with ID {$membershipId} not found!");
            return 1;
        }

        $this->info("Membership found:");
        $this->info("- ID: {$membership->id}");
        $this->info("- User ID: {$membership->user_id}");
        $this->info("- Seller ID: {$membership->seller_id}");
        $this->info("- Package ID: {$membership->package_id}");
        $this->info("- Status: {$membership->status}");
        $this->info("- Payment Method: {$membership->payment_method}");
        $this->info("- Start Date: {$membership->start_date}");
        $this->info("- Expire Date: {$membership->expire_date}");
        $this->info("- Transaction ID: {$membership->transaction_id}");
        $this->info("- Invoice: " . ($membership->invoice ?? 'null'));

        if ($membership->invoice) {
            $filePath = public_path('assets/file/invoices/seller-memberships/' . $membership->invoice);
            if (file_exists($filePath)) {
                $this->info("Invoice file exists at: {$filePath}. Deleting it to regenerate.");
                unlink($filePath);
            } else {
                $this->warn("Invoice file does not exist at: {$filePath}. No need to delete.");
            }
        }

        try {
            $invoiceName = $this->generateSellerMembershipInvoice($membership);
            $membership->update(['invoice' => $invoiceName]);
            $this->info("Generated new invoice: {$invoiceName}");
            $this->info("Membership invoice fixed!");
        } catch (\Exception $e) {
            $this->error("Failed to generate new invoice for membership #{$membership->id}: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    public function checkPaymentMethods()
    {
        $this->info('Checking payment methods for membership transactions...');
        
        $transactions = \App\Models\Transaction::where('transcation_type', 5)->get();
        
        if ($transactions->isEmpty()) {
            $this->info('No membership transactions found.');
            return;
        }
        
        foreach ($transactions as $transaction) {
            $this->info("Transaction ID: {$transaction->transcation_id}");
            $this->info("Payment Method: " . ($transaction->payment_method ?? 'NULL'));
            $this->info("Gateway Type: " . ($transaction->gateway_type ?? 'NULL'));
            
            // Check if linked to membership
            $membership = $transaction->sellerMembership()->first();
            if ($membership) {
                $this->info("Membership Payment Method: " . ($membership->payment_method ?? 'NULL'));
                $this->info("Membership Transaction Details: " . ($membership->transaction_details ?? 'NULL'));
                
                // Fix the transaction payment method if it's NULL but membership has it
                if (empty($transaction->payment_method) && !empty($membership->payment_method)) {
                    $transaction->payment_method = $membership->payment_method;
                    $transaction->save();
                    $this->info("✅ Fixed transaction payment method to: {$membership->payment_method}");
                }
            } else {
                $this->info("No linked membership found");
            }
            $this->info('---');
        }
    }

    public function fixPaymentMethods()
    {
        $this->info('Fixing payment methods for membership transactions...');
        
        $transactions = \App\Models\Transaction::where('transcation_type', 5)->get();
        
        if ($transactions->isEmpty()) {
            $this->info('No membership transactions found.');
            return 0;
        }
        
        $fixed = 0;
        
        foreach ($transactions as $transaction) {
            $userMembership = $transaction->userMembership()->first();
            $sellerMembership = $transaction->sellerMembership()->first();
            $membership = $userMembership ?: $sellerMembership;
            
            if ($membership) {
                $paymentMethod = null;
                
                // Determine payment method based on various sources
                if (!empty($membership->payment_method) && $membership->payment_method != '-' && $membership->payment_method != 'NULL') {
                    $paymentMethod = $membership->payment_method;
                } elseif (!empty($transaction->gateway_type)) {
                    if ($transaction->gateway_type == 'online') {
                        // Check transaction details for specific payment method
                        if (strpos($membership->transaction_details, 'Stripe') !== false) {
                            $paymentMethod = 'Stripe';
                        } elseif (strpos($membership->transaction_details, 'PayPal') !== false) {
                            $paymentMethod = 'PayPal';
                        } elseif (strpos($membership->transaction_details, 'Free') !== false) {
                            $paymentMethod = 'Free';
                        } else {
                            $paymentMethod = 'Online Payment';
                        }
                    } elseif ($transaction->gateway_type == 'offline') {
                        $paymentMethod = 'Offline';
                    }
                } elseif (strpos($membership->transaction_details, 'Free') !== false) {
                    $paymentMethod = 'Free';
                } else {
                    $paymentMethod = 'Online Payment';
                }
                
                if ($paymentMethod && ($transaction->payment_method != $paymentMethod)) {
                    $transaction->payment_method = $paymentMethod;
                    $transaction->save();
                    
                    // Also update membership if it has invalid payment method
                    if ($membership->payment_method == '-' || $membership->payment_method == 'NULL' || empty($membership->payment_method)) {
                        $membership->payment_method = $paymentMethod;
                        $membership->save();
                        $this->info("Updated membership payment method to: {$paymentMethod}");
                    }
                    
                    $this->info("Fixed transaction {$transaction->transcation_id}: {$paymentMethod}");
                    $fixed++;
                }
            }
        }
        
        $this->info("Fixed {$fixed} transactions.");
        return 0;
    }


} 