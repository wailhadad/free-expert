<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddUserPackageImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:user-package-imports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add necessary imports to payment controllers for user package support';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $paymentControllers = [
            'PaystackController',
            'PaytmController',
            'RazorpayController',
            'InstamojoController',
            'MercadopagoController',
            'FlutterWaveController'
        ];

        foreach ($paymentControllers as $controller) {
            $this->addImports($controller);
        }

        $this->info('User package imports added to all controllers!');
        return 0;
    }

    private function addImports($controllerName)
    {
        $controllerPath = app_path("Http/Controllers/Payment/{$controllerName}.php");
        
        if (!File::exists($controllerPath)) {
            $this->warn("Controller {$controllerName} not found, skipping...");
            return;
        }

        $content = File::get($controllerPath);
        
        // Check if imports already exist
        if (strpos($content, 'UserPackageController') !== false) {
            $this->info("Imports already exist in {$controllerName}, skipping...");
            return;
        }

        // Add imports after the existing use statements
        $imports = [
            'use App\\Http\\Controllers\\FrontEnd\\UserPackageController;',
            'use App\\Http\\Helpers\\UserPermissionHelper;',
            'use App\\Models\\UserPackage;'
        ];

        // Find the last use statement
        $lastUsePos = strrpos($content, 'use ');
        if ($lastUsePos !== false) {
            // Find the end of the last use statement
            $endOfLastUse = strpos($content, ';', $lastUsePos) + 1;
            
            // Insert new imports
            $newContent = substr($content, 0, $endOfLastUse) . "\n" . implode("\n", $imports) . "\n" . substr($content, $endOfLastUse);
            
            File::put($controllerPath, $newContent);
            $this->info("Added imports to {$controllerName}");
        }
    }
}
