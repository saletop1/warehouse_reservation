<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_no',
        'plant',
        'sloc_supply',
        'plant_supply',
        'status',
        'remarks',
        'total_qty',
        'total_transferred',
        'completion_rate',
        'created_by_name'
    ];

    protected $casts = [
        'total_qty' => 'decimal:2',
        'total_transferred' => 'decimal:2',
        'completion_rate' => 'decimal:2'
    ];

    /**
     * Get the items for the document.
     */
    public function items()
    {
        return $this->hasMany(DocumentItem::class);
    }

    /**
     * Get the transfers for the document.
     */
    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }
}
