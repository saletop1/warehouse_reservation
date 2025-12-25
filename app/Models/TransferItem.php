<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'material_code',
        'material_description',
        'batch',
        'batch_sloc',
        'quantity',
        'unit',
        'plant_supply',
        'plant_destination',
        'sloc_destination',
        'requested_qty',
        'available_stock',
        'sap_status',
        'item_number'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'requested_qty' => 'decimal:3',
        'available_stock' => 'decimal:3'
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
