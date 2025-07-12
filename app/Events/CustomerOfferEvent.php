<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerOfferEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $offer;
    public $chatId;
    public $action; // created, accepted, declined

    public function __construct($offer, $chatId, $action)
    {
        $this->offer = $offer;
        $this->chatId = $chatId;
        $this->action = $action;
    }

    public function broadcastOn()
    {
        // Use a public channel per chat
        return [new Channel('offer-channel.' . $this->chatId)];
    }

    public function broadcastAs()
    {
        return 'customer-offer.' . $this->action;
    }

    public function broadcastWith()
    {
        return [
            'offer' => $this->offer,
            'chat_id' => $this->chatId,
            'action' => $this->action,
        ];
    }
} 