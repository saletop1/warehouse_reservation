<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'sap_reservations'; // Sesuai dengan nama tabel di database

    protected $fillable = [
        'rsnum',
        'sap_plant',
        'sap_order',
        'aufnr',
        'matnr',
        'maktx',
        'psmng',
        'meins',
        'gstrp',
        'gltrp',
        'makhd',
        'mtart',
        'dwerk',
        'sync_by',
        'sync_at',
    ];

    protected $casts = [
        'psmng' => 'decimal:3',
        'gstrp' => 'date',
        'gltrp' => 'date',
        'sync_at' => 'datetime',
    ];

    // Relasi ke documents jika diperlukan
    public function documents()
    {
        return $this->hasMany(ReservationDocument::class, 'reservation_no', 'rsnum');
    }
}
