<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleNote extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'noted_at',
        'scheduled_for',
        'delivered_at',
        'delivery_latitude',
        'delivery_longitude',
        'sale_discount',
        'total',
        'status',
        'sale_id',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'noted_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_latitude' => 'decimal:7',
            'delivery_longitude' => 'decimal:7',
            'sale_discount' => 'decimal:1',
            'total' => 'decimal:1',
        ];
    }

    /**
     * Get the user that registered the sale note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the sale generated when the note was delivered.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /**
     * Get the details of the sale note.
     */
    public function saleNoteDetails(): HasMany
    {
        return $this->hasMany(SaleNoteDetail::class, 'sale_note_id');
    }
}
