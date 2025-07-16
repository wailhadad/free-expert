<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class CustomerBriefNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'customer_brief',
            'title' => $this->data['title'] ?? 'New Customer Brief',
            'message' => $this->data['message'] ?? '',
            'url' => $this->data['url'] ?? null,
            'icon' => $this->data['icon'] ?? 'fas fa-briefcase',
            'extra' => $this->data['extra'] ?? [],
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'customer_brief',
            'title' => $this->data['title'] ?? 'New Customer Brief',
            'message' => $this->data['message'] ?? '',
            'url' => $this->data['url'] ?? null,
            'icon' => $this->data['icon'] ?? 'fas fa-briefcase',
            'extra' => $this->data['extra'] ?? [],
            'created_at' => now()->toDateTimeString(),
        ]);
    }
} 