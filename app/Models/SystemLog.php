<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    protected $table = 'system_logs';

    protected $fillable = [
        'type',
        'action',
        'description',
        'data',
        'status',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log($action, $type = 'system', $description = null, $status = 'success', $data = null)
    {
        return self::create([
            'type' => $type,
            'action' => $action,
            'description' => $description,
            'data' => $data,
            'status' => $status,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
