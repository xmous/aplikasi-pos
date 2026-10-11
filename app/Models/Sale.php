<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'number','store_id','warehouse_id','cashier_shift_id','customer_id','cashier_id','promotion_id',
        'sold_at','due_date','subtotal','promotion_discount','manual_discount','total','paid_amount',
        'change_amount','payment_status','status','notes',
        'points_earned','points_redeemed','points_discount',
        'points_earn_amount','points_redeem_value',
        'points_awarded_at','points_reversed',
        'points_redeemed_restored',
    ];
    protected function casts(): array {
        return [
            'sold_at'=>'datetime','due_date'=>'date','subtotal'=>'decimal:2','promotion_discount'=>'decimal:2',
            'manual_discount'=>'decimal:2','total'=>'decimal:2','paid_amount'=>'decimal:2','change_amount'=>'decimal:2',
            'points_earned'=>'integer','points_redeemed'=>'integer',
            'points_discount'=>'decimal:2','points_earn_amount'=>'decimal:2',
            'points_redeem_value'=>'decimal:2','points_awarded_at'=>'datetime',
            'points_reversed'=>'integer','points_redeemed_restored'=>'integer',
        ];
    }
    public function items() { return $this->hasMany(SaleItem::class); }
    public function payments() { return $this->hasMany(SalePayment::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function cashier() { return $this->belongsTo(User::class, 'cashier_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function promotion() { return $this->belongsTo(Promotion::class); }
    public function returns() { return $this->hasMany(SaleReturn::class); }
    public function pointTransactions() { return $this->hasMany(CustomerPointTransaction::class); }
    public function getNetTotalAttribute(): float { return max(0, (float)$this->total - (float)$this->returns()->sum('total')); }
    public function getBalanceAttribute(): float { return max(0, $this->net_total - (float)$this->paid_amount); }
}
