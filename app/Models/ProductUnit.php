<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id', 'unit_id', 'conversion_factor', 'barcode',
        'purchase_price', 'selling_price', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product() { return $this->belongsTo(Product::class); }
    public function unit() { return $this->belongsTo(Unit::class); }

    public function purchasePrice(): float
    {
        return $this->purchase_price !== null
            ? (float) $this->purchase_price
            : (float) $this->product->purchase_price * (float) $this->conversion_factor;
    }

    public function sellingPrice(): float
    {
        return $this->selling_price !== null
            ? (float) $this->selling_price
            : (float) $this->product->selling_price * (float) $this->conversion_factor;
    }
}
