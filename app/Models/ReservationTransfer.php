<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationTransfer extends Model
{
    use HasFactory;

    protected $table = 'reservation_transfers'; // Tentukan nama tabel

    protected $fillable = [
        'document_id',
        'document_no',
        'transfer_no',
        'plant_supply',
        'plant_destination',
        'move_type',
        'total_items',
        'total_quantity',
        'status',
        'sap_message',
        'remarks',
        'created_by',
        'created_by_name',
        'completed_at'
    ];

    protected $casts = [
        'total_quantity' => 'float',
        'completed_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(ReservationDocument::class, 'document_id');
    }

    public function items()
    {
        return $this->hasMany(ReservationTransferItem::class, 'transfer_id');
    }
}
