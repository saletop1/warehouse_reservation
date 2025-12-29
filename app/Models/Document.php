<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    // Tentukan nama tabel yang benar
    protected $table = 'reservation_documents';

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
        'created_by',
        'created_by_name',
        'force_completed',
        'force_complete_reason',
        'force_completed_by',
        'force_completed_at'
    ];

    protected $casts = [
        'total_qty' => 'decimal:3',
        'total_transferred' => 'decimal:3',
        'completion_rate' => 'decimal:2',
        'force_completed' => 'boolean',
        'force_completed_at' => 'datetime'
    ];

    /**
     * Get the items for the document.
     */
    public function items()
    {
        return $this->hasMany(DocumentItem::class, 'document_id');
    }

    /**
     * Get the transfers for the document.
     */
    public function transfers()
    {
        return $this->hasMany(Transfer::class, 'document_id');
    }

    /**
     * Check if user can edit the document
     */
    public function canEdit($userId = null)
    {
        if ($userId === null) {
            $userId = auth()->id();
        }

        return $this->created_by == $userId;
    }

    /**
     * Scope untuk documents yang bisa diedit
     */
    public function scopeEditable($query)
    {
        return $query->whereIn('status', ['booked', 'partial']);
    }

    /**
     * Scope untuk documents yang belum force completed
     */
    public function scopeNotForceCompleted($query)
    {
        return $query->where('force_completed', false);
    }

    /**
     * Accessor untuk mendapatkan total items
     */
    public function getTotalItemsAttribute()
    {
        return $this->items()->count();
    }

    /**
     * Accessor untuk mendapatkan items yang belum force completed
     */
    public function getPendingItemsAttribute()
    {
        return $this->items()->where('force_completed', false)->count();
    }
}
