<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'receipt_footer',
        'receipt_policy_text',
        'logo_path',
    ];

    public function warehouses() { return $this->hasMany(Warehouse::class); }
}
