<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\Membership;

class MembershipExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $membership;

    /**
     * Create a new notification instance.
     */
    public function __construct(Membership $membership)
    {
        $this->membership = $membership;
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
        return [
            'title' => 'Membership Expired',
            'message' => 'Your membership for package "' . $this->membership->package->title . '" has expired. Please renew to continue using our services.',
            'type' => 'membership_expired',
            'membership_id' => $this->membership->id,
            'package_title' => $this->membership->package->title,
            'expire_date' => $this->membership->expire_date,
            'action_url' => route('seller.plan.extend.index'),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'notification' => [
                'id' => $this->id,
                'type' => 'membership_expired',
                'title' => 'Membership Expired',
                'message' => 'Your membership for package "' . $this->membership->package->title . '" has expired. Please renew to continue using our services.',
                'membership_id' => $this->membership->id,
                'package_title' => $this->membership->package->title,
                'expire_date' => $this->membership->expire_date,
                'action_url' => route('seller.plan.extend.index'),
                'time' => now()->toISOString(),
            ]
        ]);
    }
} 