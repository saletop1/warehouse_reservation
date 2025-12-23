<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'material_code',
        'material_description',
        'requested_qty',
        'transferred_qty',
        'unit',
        'dispo',
        'sources',
        'sales_orders',
        'pro_details',
        'sortf',
        'stock_info'
    ];

    protected $casts = [
        'requested_qty' => 'decimal:3',
        'transferred_qty' => 'decimal:3',
        'sources' => 'array',
        'sales_orders' => 'array',
        'pro_details' => 'array',
        'stock_info' => 'array'
    ];

    /**
     * Get the document that owns the item.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
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
}
