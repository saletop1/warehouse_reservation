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
        'status'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReservationDocumentItem::class, 'document_id');
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class, 'document_id');
    }
}
