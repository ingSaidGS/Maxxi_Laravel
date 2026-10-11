<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleNoteDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sale_note_id',
        'presentation_id',
        'quantity',
        'conversion_factor',
        'base_quantity',
        'unit_price',
        'base_unit_cost_at_note',
        'subtotal',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_quantity' => 'integer',
            'unit_price' => 'decimal:1',
            'base_unit_cost_at_note' => 'decimal:2',
            'subtotal' => 'decimal:1',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the sale note that owns the detail.
     */
    public function saleNote(): BelongsTo
    {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }

    /**
     * Get the presentation of the detail.
     */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class, 'presentation_id');
    }
}
