<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'number', 'purchase_id', 'created_by', 'returned_at', 'total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function items() { return $this->hasMany(PurchaseReturnItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
