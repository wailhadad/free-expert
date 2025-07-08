<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class OrderNotification extends Notification implements ShouldBroadcast, ShouldQueue
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
            'type' => 'order',
            'title' => $this->data['title'] ?? 'Order Update',
            'message' => $this->data['message'] ?? '',
            'url' => $this->data['url'] ?? null,
            'icon' => $this->data['icon'] ?? null,
            'extra' => $this->data['extra'] ?? [],
            'order_id' => $this->data['order_id'] ?? null,
            'order_number' => $this->data['order_number'] ?? null,
            'service_name' => $this->data['service_name'] ?? null,
            'service_id' => $this->data['service_id'] ?? null,
            'order_status' => $this->data['order_status'] ?? null,
            'payment_status' => $this->data['payment_status'] ?? null,
            'amount' => $this->data['amount'] ?? null,
            'currency' => $this->data['currency'] ?? null,
            'customer_name' => $this->data['customer_name'] ?? null,
            'seller_name' => $this->data['seller_name'] ?? null,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'order',
            'title' => $this->data['title'] ?? 'Order Update',
            'message' => $this->data['message'] ?? '',
            'url' => $this->data['url'] ?? null,
            'icon' => $this->data['icon'] ?? null,
            'extra' => $this->data['extra'] ?? [],
            'order_id' => $this->data['order_id'] ?? null,
            'order_number' => $this->data['order_number'] ?? null,
            'service_name' => $this->data['service_name'] ?? null,
            'service_id' => $this->data['service_id'] ?? null,
            'order_status' => $this->data['order_status'] ?? null,
            'payment_status' => $this->data['payment_status'] ?? null,
            'amount' => $this->data['amount'] ?? null,
            'currency' => $this->data['currency'] ?? null,
            'customer_name' => $this->data['customer_name'] ?? null,
            'seller_name' => $this->data['seller_name'] ?? null,
            'created_at' => now()->toDateTimeString(),
        ]);
    }
} 