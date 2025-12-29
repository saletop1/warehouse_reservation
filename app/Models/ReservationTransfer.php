<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationTransfer extends Model
{
    protected $fillable = [
        'transfer_no',
        'document_id',
        'document_no',
        'plant_supply',
        'plant_destination',
        'move_type',
        'status',
        'total_qty',
        'total_items',
        'remarks',
        'created_by',
        'created_by_name',
        'completed_at',
        'sap_message',
        'sap_response'  // Tambahkan ini jika belum ada
    ];

    protected $casts = [
        'total_qty' => 'decimal:3',
        'completed_at' => 'datetime'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReservationTransferItem::class, 'transfer_id');
    }

    public function document()
    {
        return $this->belongsTo(ReservationDocument::class);
    }
}
