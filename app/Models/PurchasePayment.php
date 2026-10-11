<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    protected $fillable = ['purchase_id','created_by','method','amount','paid_at','reference_number','notes'];
    protected function casts(): array { return ['amount'=>'decimal:2','paid_at'=>'datetime']; }
    public function purchase() { return $this->belongsTo(Purchase::class); }
}
