<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'warehouse_id', 'product_id', 'product_batch_id', 'created_by', 'type',
        'direction', 'quantity', 'stock_before', 'stock_after',
        'reference_type', 'reference_id', 'notes',
    ];
    protected function casts(): array {
        return ['quantity'=>'decimal:3','stock_before'=>'decimal:3','stock_after'=>'decimal:3'];
    }
    public function product() { return $this->belongsTo(Product::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function batch() { return $this->belongsTo(ProductBatch::class, 'product_batch_id'); }
    public function user() { return $this->belongsTo(User::class, 'created_by'); }
    public function reference() { return $this->morphTo(); }
}
