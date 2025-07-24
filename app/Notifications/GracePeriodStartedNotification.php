<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\Membership;

class GracePeriodStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $membership;
    protected $gracePeriodUntil;

    /**
     * Create a new notification instance.
     */
    public function __construct(Membership $membership, $gracePeriodUntil)
    {
        $this->membership = $membership;
        $this->gracePeriodUntil = $gracePeriodUntil;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return ['notification-channel'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $gracePeriodEnd = \Carbon\Carbon::parse($this->gracePeriodUntil);
        $timeRemaining = now()->diffForHumans($gracePeriodEnd, true);
        
        return [
            'title' => 'Grace Period Started',
            'message' => 'Your membership for package "' . $this->membership->package->title . '" is now in grace period. You have ' . $timeRemaining . ' to add funds and renew your membership.',
            'type' => 'grace_period_started',
            'membership_id' => $this->membership->id,
            'package_title' => $this->membership->package->title,
            'grace_period_until' => $this->gracePeriodUntil,
            'time_remaining' => $timeRemaining,
            'action_url' => route('seller.plan.extend.index'),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $gracePeriodEnd = \Carbon\Carbon::parse($this->gracePeriodUntil);
        $timeRemaining = now()->diffForHumans($gracePeriodEnd, true);
        
        return new BroadcastMessage([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'notification' => [
                'id' => $this->id,
                'type' => 'grace_period_started',
                'title' => 'Grace Period Started',
                'message' => 'Your membership for package "' . $this->membership->package->title . '" is now in grace period. You have ' . $timeRemaining . ' to add funds and renew your membership.',
                'membership_id' => $this->membership->id,
                'package_title' => $this->membership->package->title,
                'grace_period_until' => $this->gracePeriodUntil,
                'time_remaining' => $timeRemaining,
                'action_url' => route('seller.plan.extend.index'),
                'time' => now()->toISOString(),
            ]
        ]);
    }
} 