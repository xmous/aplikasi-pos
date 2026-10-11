<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['store_id', 'code', 'name', 'address', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function store() { return $this->belongsTo(Store::class); }
}
