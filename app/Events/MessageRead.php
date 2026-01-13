<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $readerId;

    public function __construct($messageId, $readerId)
    {
        $this->messageId = $messageId;
        $this->readerId = $readerId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('message-read');
    }

    public function broadcastAs()
    {
        return 'message-read';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'reader_id' => $this->readerId,
            'read_at' => now()->toDateTimeString()
        ];
    }
}
