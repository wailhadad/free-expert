<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddUserPackagePaymentMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:user-package-payment-methods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add user package success and cancel methods to all payment controllers';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Adding user package payment methods to all payment controllers...');
        
        $controllers = [
            'AuthorizeController',
            'FlutterWaveController', 
            'InstamojoController',
            'IyzicoController',
            'MercadopagoController',
            'MidtransController',
            'MollieController',
            'MyFatoorahController',
            'PaystackController',
            'PaytabsController',
            'PaytmController',
            'PerfectMoneyController',
            'PhonePeController',
            'RazorpayController',
            'ToyyibpayController',
            'XenditController',
            'YocoController'
        ];
        
        foreach ($controllers as $controller) {
            $this->addTransactionCreation($controller);
        }
        
        $this->info('All payment controllers updated successfully!');
    }
    
    private function addTransactionCreation($controllerName)
    {
        $filePath = app_path("Http/Controllers/Payment/{$controllerName}.php");
        
        if (!file_exists($filePath)) {
            $this->warn("Controller {$controllerName} not found, skipping...");
            return;
        }
        
        $content = file_get_contents($filePath);
        
        // Check if transaction creation already exists
        if (strpos($content, 'storeUserPackageTransaction') !== false) {
            $this->info("Controller {$controllerName} already has transaction creation, skipping...");
            return;
        }
        
        // Find the pattern to add transaction creation after
        $pattern = '/@unlink\(public_path\(\'assets\/front\/invoices\/\' \. \$file_name\)\);\s*\n\s*session\(\)->flash\(\'success\', \'Your payment has been completed\.\'\);/';
        $replacement = "@unlink(public_path('assets/front/invoices/' . \$file_name));\\n\\n            // Create transaction record for user package purchase\\n            storeUserPackageTransaction(\$lastMemb, \$requestData['payment_method'], \$bs);\\n\\n            session()->flash('success', 'Your payment has been completed.');";
        
        $newContent = preg_replace($pattern, $replacement, $content);
        
        if ($newContent !== $content) {
            file_put_contents($filePath, $newContent);
            $this->info("Added transaction creation to {$controllerName}");
        } else {
            $this->warn("Could not find pattern in {$controllerName}, manual update may be needed");
        }
    }

    private function addUserPackageMethods($controllerName)
    {
        $controllerPath = app_path("Http/Controllers/Payment/{$controllerName}.php");
        
        if (!File::exists($controllerPath)) {
            $this->warn("Controller {$controllerName} not found, skipping...");
            return;
        }

        $content = File::get($controllerPath);
        
        // Check if methods already exist
        if (strpos($content, 'userPackageSuccess') !== false) {
            $this->info("Methods already exist in {$controllerName}, skipping...");
            return;
        }

        // Add user package success method
        $successMethod = $this->getUserPackageSuccessMethod($controllerName);
        $cancelMethod = $this->getUserPackageCancelMethod($controllerName);

        // Find the last closing brace and add methods before it
        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos !== false) {
            $newContent = substr($content, 0, $lastBracePos) . "\n\n" . $successMethod . "\n\n" . $cancelMethod . "\n}";
            File::put($controllerPath, $newContent);
            $this->info("Added user package methods to {$controllerName}");
        }
    }

    private function getUserPackageSuccessMethod($controllerName)
    {
        return "    public function userPackageSuccess(Request \$request)
    {
        \$requestData = Session::get('request');
        \$bs = Basic::first();
        
        if (Session::get('paymentFor') == 'user_package') {
            \$package = \\App\\Models\\UserPackage::find(\$requestData['package_id']);
            \$transaction_id = \\App\\Http\\Helpers\\UserPermissionHelper::uniqidReal(8);
            \$transaction_details = 'Payment completed via ' . \$controllerName;
            
            \$amount = \$requestData['price'];
            \$password = uniqid('qrcode');
            \$checkout = new \\App\\Http\\Controllers\\FrontEnd\\UserPackageController();
            
            \$user = \$checkout->store(\$requestData, \$transaction_id, \$transaction_details, \$amount, \$bs, \$password);
            
            \$lastMemb = \$user->userMemberships()->orderBy('id', 'DESC')->first();
            \$activation = Carbon::parse(\$lastMemb->start_date);
            \$expire = Carbon::parse(\$lastMemb->expire_date);
            
            \$file_name = \$checkout->makeInvoice(\$requestData, 'user_package', \$user, \$password, \$amount, \$requestData['payment_method'], \$user->phone, \$bs->base_currency_symbol_position, \$bs->base_currency_symbol, \$bs->base_currency_text, \$transaction_id, \$package->title, \$lastMemb);
            
            \$mailer = new MegaMailer();
            \$data = [
                'toMail' => \$user->email,
                'toName' => \$user->fname,
                'username' => \$user->username,
                'package_title' => \$package->title,
                'package_price' => (\$bs->base_currency_text_position == 'left' ? \$bs->base_currency_text . ' ' : '') . \$package->price . (\$bs->base_currency_text_position == 'right' ? ' ' . \$bs->base_currency_text : ''),
                'activation_date' => \$activation->toFormattedDateString(),
                'expire_date' => Carbon::parse(\$expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : \$expire->toFormattedDateString(),
                'membership_invoice' => \$file_name,
                'website_title' => \$bs->website_title,
                'templateType' => 'user_package_purchase',
                'type' => 'userPackagePurchase'
            ];
            \$mailer->mailFromAdmin(\$data);
            @unlink(public_path('assets/front/invoices/' . \$file_name));
            
            session()->flash('success', 'Your payment has been completed.');
            Session::forget('request');
            Session::forget('paymentFor');
            return redirect()->route('user.packages.success');
        }
        
        return redirect()->route('user.packages.index');
    }";
    }

    private function getUserPackageCancelMethod($controllerName)
    {
        return "    public function userPackageCancel()
    {
        \$requestData = Session::get('request');
        \$paymentFor = Session::get('paymentFor');
        session()->flash('warning', __('cancel_payment'));
        if (\$paymentFor == 'user_package') {
            return redirect()->route('user.packages.checkout', ['id' => \$requestData['package_id']])->withInput(\$requestData);
        }
        return redirect()->route('user.packages.index');
    }";
    }
}
