<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;

    public function __construct(Chat $chat)
    {
        $this->chat = $chat->load(['sender', 'receiver']);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->chat->receiver_id);
    }

    public function broadcastAs()
    {
        return 'new-message';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->chat->id,
            'sender' => [
                'id' => $this->chat->sender->id,
                'name' => $this->chat->sender->name,
                'avatar' => $this->chat->sender->avatar_url ?? null
            ],
            'message' => $this->chat->message,
            'message_type' => $this->chat->message_type,
            'attachment' => $this->chat->attachment,
            'created_at' => $this->chat->created_at->toDateTimeString(),
            'is_read' => $this->chat->is_read
        ];
    }
}
