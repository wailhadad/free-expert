<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserMembership;
use App\Models\User;
use App\Models\BasicSettings\Basic;
use App\Jobs\UserMembershipExpiredMail;
use App\Jobs\UserMembershipReminderMail;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessUserMembershipExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-memberships:process-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired user memberships and send notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting user membership expiration processing...');
        Log::info('Cron job: Processing user membership expirations started');
        
        try {
            $bs = Basic::first();
            $notificationService = new NotificationService();

            // Process expired memberships
            $this->processExpiredMemberships($bs, $notificationService);

            // Process reminder notifications
            $this->processReminderNotifications($bs, $notificationService);

            $this->info('User membership expirations processed successfully!');
            Log::info('Cron job: Processing user membership expirations completed successfully');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error processing user membership expirations: ' . $e->getMessage());
            Log::error('Cron job: Error processing user membership expirations - ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Process expired memberships
     */
    private function processExpiredMemberships($bs, $notificationService)
    {
        $gracePeriodMinutes = $bs->grace_period_minutes ?? 2;

        // Find memberships that have expired but not entered grace period yet
        $expiredMemberships = UserMembership::where('status', '1')
            ->where('expire_date', '<', Carbon::now())
            ->whereNull('grace_period_until')
            ->where(function($query) {
                $query->whereNull('processed_for_expiration')
                      ->orWhere('processed_for_expiration', 0);
            })
            ->with(['user', 'package'])
            ->get();

        $this->info("Found {$expiredMemberships->count()} expired user memberships to start grace period");

        foreach ($expiredMemberships as $membership) {
            if (!empty($membership->user)) {
                $user = $membership->user;
                
                // Start grace period
                $membership->startGracePeriod($gracePeriodMinutes);

                // Update subuser statuses based on new package limits (during grace period)
                \App\Http\Helpers\UserPermissionHelper::updateSubuserStatuses($user->id);

                // Send grace period notification
                $notificationData = [
                    'type' => 'user_membership_grace_period',
                    'title' => 'Membership in Grace Period',
                    'message' => "Your membership for package '{$membership->package->title}' is now in grace period. Please renew within {$gracePeriodMinutes} minutes to avoid losing access.",
                    'url' => route('pricing'),
                    'icon' => 'fas fa-clock',
                    'extra' => [
                        'membership_id' => $membership->id,
                        'package_id' => $membership->package_id,
                        'package_title' => $membership->package->title,
                        'expire_date' => $membership->expire_date,
                        'grace_period_until' => $membership->grace_period_until,
                        'grace_period_minutes' => $gracePeriodMinutes,
                    ]
                ];

                $notificationService->sendRealTime($user, $notificationData);

                $this->info("Started grace period for user: {$user->username}");
                Log::info("User membership grace period started", [
                    'user_id' => $user->id,
                    'membership_id' => $membership->id,
                    'package_title' => $membership->package->title,
                    'grace_period_until' => $membership->grace_period_until
                ]);
            }
        }

        // Find memberships that have truly expired (after grace period)
        $trulyExpiredMemberships = UserMembership::where('status', '1')
            ->where('in_grace_period', 1)
            ->where('grace_period_until', '<', Carbon::now())
            ->where(function($query) {
                $query->whereNull('processed_for_expiration')
                      ->orWhere('processed_for_expiration', 0);
            })
            ->with(['user', 'package'])
            ->get();

        $this->info("Found {$trulyExpiredMemberships->count()} truly expired user memberships");

        foreach ($trulyExpiredMemberships as $membership) {
            if (!empty($membership->user)) {
                $user = $membership->user;
                
                // Send email notification
                UserMembershipExpiredMail::dispatch($user, $bs, $membership);

                // Send real-time notification
                $notificationData = [
                    'type' => 'user_membership_expired',
                    'title' => 'Membership Expired',
                    'message' => "Your membership for package '{$membership->package->title}' has expired. Please renew to continue accessing premium features.",
                    'url' => route('pricing'),
                    'icon' => 'fas fa-calendar-times',
                    'extra' => [
                        'membership_id' => $membership->id,
                        'package_id' => $membership->package_id,
                        'package_title' => $membership->package->title,
                        'expire_date' => $membership->expire_date,
                    ]
                ];

                $notificationService->sendRealTime($user, $notificationData);

                // Update subuser statuses based on new package limits (after expiration)
                \App\Http\Helpers\UserPermissionHelper::updateSubuserStatuses($user->id);

                // Mark as processed
                $membership->update(['processed_for_expiration' => 1]);

                $this->info("Processed truly expired membership for user: {$user->username}");
                Log::info("User membership truly expired processed", [
                    'user_id' => $user->id,
                    'membership_id' => $membership->id,
                    'package_title' => $membership->package->title
                ]);
            }
        }
    }

    /**
     * Process reminder notifications
     */
    private function processReminderNotifications($bs, $notificationService)
    {
        $reminderDays = $bs->expiration_reminder ?? 7; // Default to 7 days if not set
        
        $reminderMemberships = UserMembership::where('status', '1')
            ->whereDate('expire_date', Carbon::now()->addDays($reminderDays))
            ->where(function($query) {
                $query->whereNull('reminder_sent')
                      ->orWhere('reminder_sent', 0);
            })
            ->with(['user', 'package'])
            ->get();

        $this->info("Found {$reminderMemberships->count()} user memberships for reminder");

        foreach ($reminderMemberships as $membership) {
            if (!empty($membership->user)) {
                $user = $membership->user;

                // Send email reminder
                UserMembershipReminderMail::dispatch($user, $bs, $membership->expire_date, $membership);

                // Send real-time notification
                $notificationData = [
                    'type' => 'user_membership_reminder',
                    'title' => 'Membership Expiring Soon',
                    'message' => "Your membership for package '{$membership->package->title}' will expire on {$membership->expire_date}. Please renew to avoid service interruption.",
                    'url' => route('pricing'),
                    'icon' => 'fas fa-clock',
                    'extra' => [
                        'membership_id' => $membership->id,
                        'package_id' => $membership->package_id,
                        'package_title' => $membership->package->title,
                        'expire_date' => $membership->expire_date,
                        'days_remaining' => $reminderDays,
                    ]
                ];

                $notificationService->sendRealTime($user, $notificationData);

                // Mark reminder as sent
                $membership->update(['reminder_sent' => 1]);

                $this->info("Sent reminder for user: {$user->username}");
                Log::info("User membership reminder sent", [
                    'user_id' => $user->id,
                    'membership_id' => $membership->id,
                    'package_title' => $membership->package->title,
                    'expire_date' => $membership->expire_date
                ]);
            }
        }
    }
} 