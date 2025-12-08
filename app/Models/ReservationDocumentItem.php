<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationDocumentItem extends Model
{
    use HasFactory;

    protected $table = 'reservation_document_items';

    protected $fillable = [
        'document_id',
        'material_code',
        'material_description',
        'unit',
        'requested_qty',
        'sources',
        'pro_details'
    ];

    protected $casts = [
        'requested_qty' => 'decimal:3',
        'sources' => 'array',
        'pro_details' => 'array'
    ];

    public function document()
    {
        return $this->belongsTo(ReservationDocument::class, 'document_id');
    }
}
