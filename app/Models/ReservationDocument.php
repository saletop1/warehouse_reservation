<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationDocument extends Model
{
    use HasFactory;

    protected $table = 'reservation_documents';

    protected $fillable = [
        'document_no',
        'plant',
        'status',
        'total_items',
        'total_qty',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'total_qty' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(ReservationDocumentItem::class, 'document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
