<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class BroadcastServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    // Set Pusher configuration from database
    $this->setPusherConfig();
  
    Broadcast::routes(); 
    require base_path('routes/channels.php');
  }

  /**
   * Set Pusher configuration from database
   */
  protected function setPusherConfig()
  {
    try {
      $bs = DB::table('basic_settings')->select('pusher_key', 'pusher_secret', 'pusher_app_id', 'pusher_cluster')->first();
      
      if ($bs) {
        config([
          'broadcasting.connections.pusher.key' => $bs->pusher_key,
          'broadcasting.connections.pusher.secret' => $bs->pusher_secret,
          'broadcasting.connections.pusher.app_id' => $bs->pusher_app_id,
          'broadcasting.connections.pusher.options.cluster' => $bs->pusher_cluster,
        ]);
      }
    } catch (\Exception $e) {
      // If database is not available, use environment variables
      \Log::warning('Could not load Pusher config from database: ' . $e->getMessage());
    }
  }
}
