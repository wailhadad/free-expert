<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Seller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class TestNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:notification {type=all} {--user-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test real-time notification';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');
        $userId = $this->option('user-id');

        $data = [
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'This is a test real-time notification sent at ' . now()->format('H:i:s'),
            'url' => '/notifications',
            'icon' => 'bi bi-bell',
            'extra' => [
                'test' => true,
                'timestamp' => now()->toISOString()
            ]
        ];

        if ($userId) {
            // Send to specific user
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return 1;
            }
            
            $this->info("Sending notification to user: {$user->name} (ID: {$user->id})");
            $this->notificationService->sendRealTime($user, $data);
            $this->info("Test notification sent to user: {$user->name} (ID: {$user->id})");
        } else {
            // Send to all users of specified type
            switch ($type) {
                case 'admin':
                    $admins = Admin::all();
                    $this->info("Found " . $admins->count() . " admins");
                    $this->notificationService->notifyAdmins($data);
                    $this->info('Test notification sent to all admins');
                    break;
                case 'seller':
                    $sellers = Seller::all();
                    $this->info("Found " . $sellers->count() . " sellers");
                    $this->notificationService->notifySellers($data);
                    $this->info('Test notification sent to all sellers');
                    break;
                case 'user':
                    $users = User::all();
                    $this->info("Found " . $users->count() . " users");
                    $this->notificationService->notifyUsers($data);
                    $this->info('Test notification sent to all users');
                    break;
                case 'all':
                    $admins = Admin::all();
                    $sellers = Seller::all();
                    $users = User::all();
                    $this->info("Found " . $admins->count() . " admins, " . $sellers->count() . " sellers, " . $users->count() . " users");
                    $this->notificationService->notifyAll($data);
                    $this->info('Test notification sent to all users (admins, sellers, users)');
                    break;
                default:
                    $this->error("Invalid type: {$type}. Use: admin, seller, user, or all");
                    return 1;
            }
        }

        return 0;
    }
} 