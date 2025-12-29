<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReservationDocumentItem extends Model
{
    protected $fillable = [
        'document_id',
        'material_code',
        'material_description',
        'unit',
        'sortf',
        'dispo',
        'dispc',
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
        'transferred_qty' => 'decimal:3',
        'remaining_qty' => 'decimal:3'
    ];

    // Set default value untuk transferred_qty
    protected $attributes = [
        'transferred_qty' => 0,
        'remaining_qty' => 0
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
        if (!empty($value)) {
            return $value;
        }

        $proDetails = json_decode($this->attributes['pro_details'] ?? '[]', true);
        if (!empty($proDetails) && isset($proDetails[0]['sortf'])) {
            return $proDetails[0]['sortf'];
        }

        return null;
    }

    /**
     * Accessor untuk remaining_qty - PERBAIKAN: Hitung dari database
     */
    public function getRemainingQtyAttribute()
    {
        // Jika sudah ada nilai di database, gunakan itu
        if (isset($this->attributes['remaining_qty']) && $this->attributes['remaining_qty'] !== null) {
            return (float)$this->attributes['remaining_qty'];
        }

        $requested = (float)$this->requested_qty;
        $transferred = (float)$this->transferred_qty;

        return max(0, $requested - $transferred);
    }

    /**
     * Accessor untuk status transfer
     */
    public function getTransferStatusAttribute()
    {
        $transferred = (float)$this->transferred_qty;
        $requested = (float)$this->requested_qty;
        $remaining = $this->remaining_qty;

        if ($remaining == 0 && $requested > 0) {
            return 'completed';
        } elseif ($transferred > 0 && $remaining > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }

    /**
     * Hitung transferred_qty dari database
     */
    public function calculateTransferredQty()
    {
        $transferredQty = DB::table('reservation_transfer_items')
            ->where('document_item_id', $this->id)
            ->sum('quantity');

        $this->transferred_qty = (float)$transferredQty;
        $this->remaining_qty = max(0, (float)$this->requested_qty - (float)$transferredQty);
        $this->save();

        return $this;
    }
}
