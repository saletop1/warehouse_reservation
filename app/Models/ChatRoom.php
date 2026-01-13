<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'participants',
        'created_by'
    ];

    protected $casts = [
        'participants' => 'array'
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->latest();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_room_users')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function unreadCount($userId)
    {
        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->where('created_at', '>', function($query) use ($userId) {
                $query->select('last_read_at')
                    ->from('chat_room_users')
                    ->where('chat_room_id', $this->id)
                    ->where('user_id', $userId)
                    ->limit(1);
            })
            ->count();
    }
}
