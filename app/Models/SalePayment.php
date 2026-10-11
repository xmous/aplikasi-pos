<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $fillable = ['sale_id','created_by','method','amount','paid_at','reference_number','notes'];
    protected function casts(): array { return ['amount'=>'decimal:2','paid_at'=>'datetime']; }
    public function sale() { return $this->belongsTo(Sale::class); }
}
