<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_category_id', 'unit_id', 'sku', 'barcode', 'name',
        'purchase_price', 'average_cost', 'selling_price', 'wholesale_price',
        'wholesale_min_qty', 'minimum_stock', 'rack_location', 'image_path',
        'track_stock', 'has_expiry', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2', 'average_cost' => 'decimal:2',
            'selling_price' => 'decimal:2', 'wholesale_price' => 'decimal:2',
            'wholesale_min_qty' => 'decimal:3', 'minimum_stock' => 'decimal:3',
            'track_stock' => 'boolean', 'has_expiry' => 'boolean', 'is_active' => 'boolean',
        ];
    }

    public function category() { return $this->belongsTo(ProductCategory::class, 'product_category_id'); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function conversionUnits() { return $this->hasMany(ProductUnit::class); }
    public function stocks() { return $this->hasMany(Stock::class); }
    public function batches() { return $this->hasMany(ProductBatch::class); }

    public function priceFor(float $baseQuantity): float
    {
        if ($this->wholesale_price !== null && $this->wholesale_min_qty !== null && $baseQuantity >= (float) $this->wholesale_min_qty) {
            return (float) $this->wholesale_price;
        }
        return (float) $this->selling_price;
    }
}
