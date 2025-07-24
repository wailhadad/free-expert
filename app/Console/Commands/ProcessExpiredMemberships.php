<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CronJobController;
use Illuminate\Support\Facades\Log;

class ProcessExpiredMemberships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'memberships:process-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired memberships and trigger auto-renewal';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting expired membership processing...');
        Log::info('Cron job: Processing expired memberships started');
        
        try {
            $controller = new CronJobController();
            $controller->expired();
            
            $this->info('Expired memberships processed successfully!');
            Log::info('Cron job: Processing expired memberships completed successfully');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error processing expired memberships: ' . $e->getMessage());
            Log::error('Cron job: Error processing expired memberships - ' . $e->getMessage());
            return 1;
        }
    }
}
