<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function products() { return $this->hasMany(Product::class); }
}
