<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $chatId;
    public $senderId;
    public $senderType;
    public $createdAt;

    public function __construct($message, $chatId, $senderId, $senderType, $createdAt)
    {
        $this->message = $message;
        $this->chatId = $chatId;
        $this->senderId = $senderId;
        $this->senderType = $senderType;
        $this->createdAt = $createdAt;
    }

    public function broadcastOn()
    {
        return [new PrivateChannel('direct-chat.' . $this->chatId)];
    }

    public function broadcastAs()
    {
        return 'direct-chat.message';
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'chat_id' => $this->chatId,
            'sender_id' => $this->senderId,
            'sender_type' => $this->senderType,
            'created_at' => $this->createdAt,
        ];
    }
}
