<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationTransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'document_item_id',
        'material_code',
        'material_description',
        'unit',
        'quantity',
        'batch',
        'storage_location'
    ];

    protected $casts = [
        'quantity' => 'decimal:3'
    ];

    public function transfer()
    {
        return $this->belongsTo(ReservationTransfer::class);
    }

    public function documentItem()
    {
        return $this->belongsTo(ReservationDocumentItem::class, 'document_item_id');
    }
}
