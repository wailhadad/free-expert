<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ReviewNotification extends Notification implements ShouldBroadcast
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
            'type' => 'review',
            'title' => $this->data['title'] ?? 'Review Update',
            'message' => $this->data['message'] ?? '',
            'url' => $this->data['url'] ?? null,
            'icon' => $this->data['icon'] ?? null,
            'extra' => $this->data['extra'] ?? [],
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'review',
            'title' => $this->data['title'] ?? 'Review Update',
            'message' => $this->data['message'] ?? '',
            'url' => $this->data['url'] ?? null,
            'icon' => $this->data['icon'] ?? null,
            'extra' => $this->data['extra'] ?? [],
            'created_at' => now()->toDateTimeString(),
        ]);
    }
} 