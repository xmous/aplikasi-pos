<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'minimum_transaction',
        'maximum_discount',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'minimum_transaction' => 'decimal:2',
            'maximum_discount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'promotion_product',
            'promotion_id',
            'product_id'
        );
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}
