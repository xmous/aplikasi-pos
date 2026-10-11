<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $fillable = [
        'cashier_shift_id', 'expense_id', 'created_by', 'type', 'amount', 'category', 'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
