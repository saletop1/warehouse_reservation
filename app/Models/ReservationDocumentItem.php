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
            'dispc', // TAMBAHAN
            'is_qty_editable',
            'requested_qty',
            'transferred_qty',
            'remaining_qty',
            'sources',
            'sales_orders',
            'pro_details',
            'mathd',
            'makhd',
            'groes',
            'ferth',
            'zeinr'
        ];

    protected $casts = [
        'sources' => 'array',
        'sales_orders' => 'array',
        'pro_details' => 'array',
        'additional_info' => 'array',
        'is_qty_editable' => 'boolean',
        'requested_qty' => 'decimal:3',
        'transferred_qty' => 'decimal:3'
    ];

    // Set default value untuk transferred_qty
    protected $attributes = [
        'transferred_qty' => 0
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

    /**
     * Accessor untuk remaining_qty
     */
    public function getRemainingQtyAttribute()
    {
        $requested = (float)$this->requested_qty;
        $transferred = (float)$this->transferred_qty;

        return max(0, $requested - $transferred);
    }

    /**
     * Accessor untuk status transfer
     */
    public function getTransferStatusAttribute()
    {
        $requested = (float)$this->requested_qty;
        $transferred = (float)$this->transferred_qty;

        if ($transferred >= $requested) {
            return 'completed';
        } elseif ($transferred > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }
}
