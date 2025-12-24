<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationDocument extends Model
{
    protected $fillable = [
        'document_no',
        'plant',
        'sloc_supply',
        'remarks',
        'created_by',
        'created_by_name',
        'status',
        'total_qty',
        'total_transferred',
        'completion_rate'
    ];

    protected $casts = [
        'total_qty' => 'decimal:3',
        'total_transferred' => 'decimal:3',
        'completion_rate' => 'decimal:2'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReservationDocumentItem::class, 'document_id');
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class, 'document_id');
    }

    /**
     * Accessor untuk total_qty dari items
     */
    public function getTotalQtyAttribute($value)
    {
        // Jika sudah ada nilai di database, gunakan itu
        if (!is_null($value)) {
            return (float)$value;
        }

        // Hitung dari items
        return $this->items->sum('requested_qty');
    }

    /**
     * Accessor untuk total_transferred dari items
     */
    public function getTotalTransferredAttribute($value)
    {
        // Jika sudah ada nilai di database, gunakan itu
        if (!is_null($value)) {
            return (float)$value;
        }

        // Hitung dari items
        return $this->items->sum('transferred_qty');
    }

    /**
     * Accessor untuk completion_rate
     */
    public function getCompletionRateAttribute($value)
    {
        // Jika sudah ada nilai di database, gunakan itu
        if (!is_null($value)) {
            return (float)$value;
        }

        // Hitung berdasarkan total transferred dan total qty
        $totalQty = $this->total_qty;
        $totalTransferred = $this->total_transferred;

        if ($totalQty > 0) {
            return round(($totalTransferred / $totalQty) * 100, 2);
        }

        return 0;
    }

    // App\Models\ReservationDocument.php
        public function recalculateTotals()
        {
            $totalTransferred = 0;
            $totalRequested = 0;

            foreach ($this->items as $item) {
                // Hitung transferred_qty dari reservation_transfer_items
                $transferredQty = DB::table('reservation_transfer_items')
                    ->where('document_item_id', $item->id)
                    ->sum('quantity');

                $item->transferred_qty = $transferredQty;
                $item->save();

                $totalTransferred += $transferredQty;
                $totalRequested += $item->requested_qty;
            }

            $this->total_transferred = $totalTransferred;
            $this->total_qty = $totalRequested;
            $this->completion_rate = $totalRequested > 0 ? ($totalTransferred / $totalRequested) * 100 : 0;
            $this->save();
        }
}
