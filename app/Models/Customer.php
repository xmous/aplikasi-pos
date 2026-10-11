<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name', 'phone', 'email', 'address', 'points', 'credit_limit', 'is_active'];
    protected function casts(): array {
        return ['points' => 'integer', 'credit_limit' => 'decimal:2', 'is_active' => 'boolean'];
    }
    public function sales() { return $this->hasMany(Sale::class); }
    public function pointTransactions() { return $this->hasMany(CustomerPointTransaction::class); }
}
