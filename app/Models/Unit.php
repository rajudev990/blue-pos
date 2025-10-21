<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $guarded = [];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'unit_id', 'id');
    }
}
