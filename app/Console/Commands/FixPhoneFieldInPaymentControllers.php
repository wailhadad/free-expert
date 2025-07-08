<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixPhoneFieldInPaymentControllers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $paymentControllers = [
            'PaypalController',
            'StripeController',
            'PaystackController',
            'PaytmController',
            'RazorpayController',
            'InstamojoController',
            'MercadopagoController',
            'FlutterWaveController',
            'AuthorizeController',
            'MollieController',
            'PhonePeController',
            'YocoController',
            'PerfectMoneyController',
            'ToyyibpayController',
            'PaytabsController',
            'IyzicoController',
            'MyFatoorahController',
            'MidtransController',
            'XenditController'
        ];

        foreach ($paymentControllers as $controller) {
            $this->fixPhoneField($controller);
        }

        $this->info('Phone field fixed in all payment controllers!');
        return 0;
    }

    private function fixPhoneField($controllerName)
    {
        $controllerPath = app_path("Http/Controllers/Payment/{$controllerName}.php");
        
        if (!File::exists($controllerPath)) {
            $this->warn("Controller {$controllerName} not found, skipping...");
            return;
        }

        $content = File::get($controllerPath);
        
        // Replace user->phone with user->phone_number
        $newContent = str_replace('$user->phone', '$user->phone_number', $content);
        
        if ($content !== $newContent) {
            File::put($controllerPath, $newContent);
            $this->info("Fixed phone field in {$controllerName}");
        } else {
            $this->info("No changes needed in {$controllerName}");
        }
    }
}
