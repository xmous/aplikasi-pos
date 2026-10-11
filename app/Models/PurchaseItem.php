<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id','product_id','product_unit_id','unit_id','quantity','conversion_factor','base_quantity',
        'unit_cost','discount','subtotal','batch_number','expired_at',
    ];
    protected function casts(): array {
        return [
            'quantity'=>'decimal:3','conversion_factor'=>'decimal:6','base_quantity'=>'decimal:3',
            'unit_cost'=>'decimal:2','discount'=>'decimal:2','subtotal'=>'decimal:2','expired_at'=>'date',
        ];
    }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function productUnit() { return $this->belongsTo(ProductUnit::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
