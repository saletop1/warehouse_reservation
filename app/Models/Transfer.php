<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
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
        'total_quantity' => 'decimal:2',
        'completed_at' => 'datetime'
    ];

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function document()
    {
        return $this->belongsTo(ReservationDocument::class, 'document_id');
    }
}
