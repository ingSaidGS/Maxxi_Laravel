<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'sold_at',
        'total',
        'status',
        'user_id',
        'sale_discount',
        'cash',
        'qr',
        'debt',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'total' => 'decimal:1',
            'sale_discount' => 'decimal:1',
            'cash' => 'decimal:1',
            'qr' => 'decimal:1',
            'debt' => 'decimal:1',
        ];
    }

    /**
     * Get the user that registered the sale.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the details of the sale.
     */
    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleDetail::class, 'sale_id');
    }
}
