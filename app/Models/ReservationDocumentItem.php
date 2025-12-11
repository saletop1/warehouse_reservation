<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservationDocumentItem extends Model
{
    protected $fillable = [
        'document_id',
        'material_code',
        'material_description',
        'unit',
        'sortf',
        'dispo',
        'is_qty_editable',
        'requested_qty',
        'sources',
        'sales_orders',
        'pro_details',
        'additional_info'
    ];

    protected $casts = [
        'sources' => 'array',
        'sales_orders' => 'array',
        'pro_details' => 'array',
        'additional_info' => 'array',
        'is_qty_editable' => 'boolean',
        'requested_qty' => 'decimal:3'
    ];

    public function document()
    {
        return $this->belongsTo(ReservationDocument::class);
    }

    /**
     * Accessor untuk mendapatkan sortf dari pro_details jika tidak ada di kolom sortf
     */
    public function getSortfAttribute($value)
    {
        // Jika ada di kolom sortf, gunakan itu
        if (!empty($value)) {
            return $value;
        }

        // Jika tidak, coba ambil dari pro_details pertama
        $proDetails = json_decode($this->attributes['pro_details'] ?? '[]', true);
        if (!empty($proDetails) && isset($proDetails[0]['sortf'])) {
            return $proDetails[0]['sortf'];
        }

        return null;
    }
}
