<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'base_unit_id',
        'stock',
        'base_unit_cost',
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
            'stock' => 'integer',
            'base_unit_cost' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the base unit of the product.
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get the presentations of the product.
     */
    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class, 'product_id');
    }
}
