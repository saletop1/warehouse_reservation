<?php
// app/Models/ReservationStock.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationStock extends Model
{
    use HasFactory;

    protected $table = 'reservation_stocks';

    protected $fillable = [
        'document_no',
        'matnr',
        'mtbez',
        'maktx',
        'werk',
        'lgort',
        'charg',
        'clabs',
        'meins',
        'vbeln',
        'posnr',
        'stock_date',
        'sync_by',
        'sync_at'
    ];

    protected $casts = [
        'clabs' => 'decimal:3',
        'stock_date' => 'datetime',
        'sync_at' => 'datetime'
    ];

    /**
     * Relationship dengan reservation document
     */
    public function reservationDocument()
    {
        return $this->belongsTo(ReservationDocument::class, 'document_no', 'document_no');
    }

    /**
     * Scope untuk dokumen tertentu
     */
    public function scopeByDocument($query, $documentNo)
    {
        return $query->where('document_no', $documentNo);
    }

    /**
     * Scope untuk material tertentu
     */
    public function scopeByMaterial($query, $matnr)
    {
        return $query->where('matnr', $matnr);
    }

    /**
     * Scope untuk plant tertentu
     */
    public function scopeByPlant($query, $plant)
    {
        return $query->where('werk', $plant);
    }

    /**
     * Format material code (remove leading zeros)
     */
    public function getFormattedMatnrAttribute()
    {
        if (ctype_digit($this->matnr)) {
            return ltrim($this->matnr, '0');
        }
        return $this->matnr;
    }
}
