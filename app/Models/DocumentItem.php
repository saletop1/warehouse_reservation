<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentItem extends Model
{
    use HasFactory;

    protected $table = 'reservation_document_items';

    protected $fillable = [
        'document_id',
        'material_code',
        'material_description',
        'requested_qty',
        'transferred_qty',
        'remaining_qty',
        'unit',
        'dispo',
        'dispc',
        'sources',
        'sales_orders',
        'pro_details',
        'sortf',
        'stock_info',
        'force_completed',
        'force_complete_reason',
        'force_completed_by',
        'force_completed_at'
    ];

    protected $casts = [
        'requested_qty' => 'decimal:3',
        'transferred_qty' => 'decimal:3',
        'remaining_qty' => 'decimal:3',
        'sources' => 'array',
        'sales_orders' => 'array',
        'pro_details' => 'array',
        'stock_info' => 'array',
        'force_completed' => 'boolean',
        'force_completed_at' => 'datetime'
    ];

    /**
     * Get the document that owns the item.
     */
    public function document()
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Get the user who force completed the item.
     */
    public function forceCompletedBy()
    {
        return $this->belongsTo(User::class, 'force_completed_by');
    }

    /**
     * Accessor for processed_sources
     */
    public function getProcessedSourcesAttribute()
    {
        $sources = $this->sources;

        if (is_array($sources)) {
            return array_map(function($source) {
                return \App\Helpers\NumberHelper::removeLeadingZeros($source);
            }, $sources);
        }

        return [];
    }

    /**
     * Check if item quantity is editable
     */
    public function getIsQtyEditableAttribute()
    {
        // Jika sudah force completed, tidak bisa diedit
        if ($this->force_completed) {
            return false;
        }

        $allowedMRP = ['PN1', 'PV1', 'PV2', 'CP1', 'CP2', 'EB2', 'UH1', 'D21', 'D22', 'GF1', 'CH4', 'D26', 'D28', 'D23', 'DR1', 'DR2', 'WE2', 'GW2'];

        if ($this->dispo && !in_array($this->dispo, $allowedMRP)) {
            return false;
        }

        return true;
    }

    /**
     * Check if item is selectable for force complete
     */
    public function getIsSelectableAttribute()
    {
        return !$this->force_completed;
    }
}
