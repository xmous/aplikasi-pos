<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = [
        'number',
        'sale_id',
        'created_by',
        'returned_at',
        'total',
        'refund_amount',
        'refund_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'total' => 'decimal:2',
            'refund_amount' => 'decimal:2',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
