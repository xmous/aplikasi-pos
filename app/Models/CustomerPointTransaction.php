<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPointTransaction extends Model
{
    protected $fillable = [
        'customer_id',
        'sale_id',
        'sale_return_id',
        'type',
        'points',
        'balance_after',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
