<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TestBroadcastingCommand extends Command
{
    protected $signature = 'test:broadcasting';
    protected $description = 'Test broadcasting configuration';

    public function handle()
    {
        $this->info('Testing broadcasting configuration...');
        
        // Check database values
        try {
            $bs = DB::table('basic_settings')->select('pusher_key', 'pusher_secret', 'pusher_app_id', 'pusher_cluster')->first();
            if ($bs) {
                $this->info('Database values:');
                $this->line('Key: ' . $bs->pusher_key);
                $this->line('Secret: ' . substr($bs->pusher_secret, 0, 10) . '...');
                $this->line('App ID: ' . $bs->pusher_app_id);
                $this->line('Cluster: ' . $bs->pusher_cluster);
            } else {
                $this->error('No basic_settings found in database');
            }
        } catch (\Exception $e) {
            $this->error('Database error: ' . $e->getMessage());
        }
        
        // Check config values
        $this->info('Config values:');
        $this->line('Driver: ' . Config::get('broadcasting.default'));
        $this->line('Pusher Key: ' . Config::get('broadcasting.connections.pusher.key'));
        $this->line('Pusher Secret: ' . (Config::get('broadcasting.connections.pusher.secret') ? substr(Config::get('broadcasting.connections.pusher.secret'), 0, 10) . '...' : 'null'));
        $this->line('Pusher App ID: ' . Config::get('broadcasting.connections.pusher.app_id'));
        $this->line('Pusher Cluster: ' . Config::get('broadcasting.connections.pusher.options.cluster'));
        
        // Test broadcasting
        try {
            $pusher = new \Pusher\Pusher(
                Config::get('broadcasting.connections.pusher.key'),
                Config::get('broadcasting.connections.pusher.secret'),
                Config::get('broadcasting.connections.pusher.app_id'),
                Config::get('broadcasting.connections.pusher.options')
            );
            
            $this->info('Pusher instance created successfully');
            
            // Test connection
            $response = $pusher->get('/channels');
            $this->info('Pusher connection test: ' . ($response->status === 200 ? 'SUCCESS' : 'FAILED'));
            
        } catch (\Exception $e) {
            $this->error('Pusher error: ' . $e->getMessage());
        }
    }
} 