<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id','product_id','product_unit_id','unit_id','product_name','sku','quantity',
        'conversion_factor','base_quantity','unit_price','unit_cost','discount','subtotal',
    ];
    protected function casts(): array {
        return [
            'quantity'=>'decimal:3','conversion_factor'=>'decimal:6','base_quantity'=>'decimal:3',
            'unit_price'=>'decimal:2','unit_cost'=>'decimal:2','discount'=>'decimal:2','subtotal'=>'decimal:2',
        ];
    }
    public function sale() { return $this->belongsTo(Sale::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function productUnit() { return $this->belongsTo(ProductUnit::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
    public function returnItems() { return $this->hasMany(SaleReturnItem::class); }
    public function getReturnedQuantityAttribute(): float { return (float)$this->returnItems()->sum('quantity'); }
}
