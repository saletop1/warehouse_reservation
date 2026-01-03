<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationTransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'document_item_id',
        'material_code',
        'material_code_raw',
        'material_description',
        'unit',
        'quantity',
        'batch',
        'storage_location',
        'plant_supply',
        'plant_destination',
        'sloc_destination',
        'item_number',
        'sap_status',
        'sap_message',
        'material_formatted',
        'requested_qty',
        'available_stock',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'material_formatted' => 'boolean'
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
