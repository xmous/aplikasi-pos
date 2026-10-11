<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = ['number','warehouse_id','created_by','approved_by','opname_date','status','submitted_at','approved_at','notes'];
    protected function casts(): array {
        return ['opname_date'=>'date','submitted_at'=>'datetime','approved_at'=>'datetime'];
    }
    public function items() { return $this->hasMany(StockOpnameItem::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
