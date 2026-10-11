<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'store_id',
        'cashier_shift_id',
        'created_by',
        'expense_date',
        'category',
        'amount',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class);
    }
}
