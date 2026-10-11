<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'number', 'store_id', 'warehouse_id', 'supplier_id', 'created_by',
        'purchase_date', 'due_date', 'supplier_invoice', 'subtotal', 'discount',
        'additional_cost', 'total', 'paid_amount', 'payment_status', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'additional_cost' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function items() { return $this->hasMany(PurchaseItem::class); }
    public function payments() { return $this->hasMany(PurchasePayment::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function returns() { return $this->hasMany(PurchaseReturn::class); }

    public function getNetTotalAttribute(): float
    {
        $returnedTotal = array_key_exists('returns_sum_total', $this->attributes)
            ? (float) ($this->attributes['returns_sum_total'] ?? 0)
            : (float) $this->returns()->sum('total');

        return max(0, (float) $this->total - $returnedTotal);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, $this->net_total - (float) $this->paid_amount);
    }
}
