<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  /**
   * The Artisan commands provided by your application.
   *
   * @var array
   */
  protected $commands = [
    //
  ];

  /**
   * Define the application's command schedule.
   *
   * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
   * @return void
   */
  protected function schedule(Schedule $schedule): void
  {
    // $schedule->command('inspire')->hourly();
    
    // Process expired memberships every minute
    $schedule->command('memberships:process-expired')->everyMinute();
    
    // Process expired user memberships every minute
    $schedule->command('user-memberships:process-expirations')->everyMinute();
    
    // Auto-fix missing invoices every hour
    $schedule->command('test:email --fix-all-invoices')->hourly();
    
    // Auto-fix orphaned transactions every hour
    $schedule->command('test:email --fix-orphaned-transactions')->hourly();
    
    // Auto-fix missing seller_id in transactions every hour
    $schedule->command('test:email --fix-missing-seller-id')->hourly();
    
    // Auto-fix missing membership invoices every hour
    $schedule->command('test:email --membership-invoices')->hourly();
    
    // Auto-fix all transactions with missing seller_id or user_id every hour
    $schedule->command('test:email --fix-all-transactions')->hourly();
    
    // Update subuser statuses daily to ensure they stay in sync with package limits
    $schedule->command('subusers:update-statuses')->daily();
  }

  /**
   * Register the commands for the application.
   *
   * @return void
   */
  protected function commands()
  {
    $this->load(__DIR__ . '/Commands');

    require base_path('routes/console.php');
  }
}
