<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sale_id',
        'presentation_id',
        'quantity',
        'conversion_factor',
        'base_quantity',
        'unit_price',
        'base_unit_cost_at_sale',
        'subtotal',
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
            'base_unit_cost_at_sale' => 'decimal:2',
            'subtotal' => 'decimal:1',
        ];
    }

    /**
     * Get the sale that owns the detail.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /**
     * Get the presentation of the detail.
     */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class, 'presentation_id');
    }
}
