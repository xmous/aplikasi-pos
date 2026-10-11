<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = ['warehouse_id', 'product_id', 'purchase_item_id', 'batch_number', 'expired_at', 'quantity'];
    protected function casts(): array { return ['expired_at' => 'date', 'quantity' => 'decimal:3']; }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function purchaseItem() { return $this->belongsTo(PurchaseItem::class); }
}
