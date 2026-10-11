<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    protected $fillable = ['stock_opname_id','product_id','system_quantity','physical_quantity','difference','notes'];
    protected function casts(): array {
        return ['system_quantity'=>'decimal:3','physical_quantity'=>'decimal:3','difference'=>'decimal:3'];
    }
    public function opname() { return $this->belongsTo(StockOpname::class, 'stock_opname_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
