<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationTransferItem extends Model
{
    use HasFactory;

    protected $table = 'reservation_transfer_items'; // Tentukan nama tabel

    protected $fillable = [
        'transfer_id',
        'item_number',
        'material_code',
        'material_code_raw',
        'batch',
        'batch_sloc',
        'quantity',
        'unit',
        'unit_sap',
        'plant_supply',
        'sloc_supply',
        'plant_destination',
        'sloc_destination',
        'sales_ord',
        's_ord_item',
        'sap_status',
        'sap_message',
        'material_formatted'
    ];

    public function transfer()
    {
        return $this->belongsTo(ReservationTransfer::class, 'transfer_id');
    }
}
