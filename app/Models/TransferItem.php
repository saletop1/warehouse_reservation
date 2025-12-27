<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'sap_status',
        'item_number'
    ];

    protected $casts = [
        'quantity' => 'decimal:3'
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
