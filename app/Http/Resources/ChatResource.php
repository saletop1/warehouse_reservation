<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'message_type' => $this->message_type,
            'attachment' => $this->attachment,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar' => $this->sender->avatar_url
            ],
            'receiver' => $this->receiver ? [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
                'avatar' => $this->receiver->avatar_url
            ] : null
        ];
    }
}
