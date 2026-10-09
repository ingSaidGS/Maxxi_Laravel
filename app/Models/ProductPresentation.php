<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPresentation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_factor',
        'sale_price',
        'purchase_enable',
        'sale_enable',
        'barcode',
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
            'sale_price' => 'decimal:1',
            'purchase_enable' => 'boolean',
            'sale_enable' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the product that owns the presentation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the unit of the presentation.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the sale details of the presentation.
     */
    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class, 'presentation_id');
    }
}
