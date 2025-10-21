<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $guarded = [];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    // items relation
   public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'purchase_id', 'id');
    }


    // Total sold qty
    public function soldQty()
    {
        return $this->saleItems()->sum('qty');
    }

    // Remaining stock
    public function stock()
    {
        return $this->qty - $this->soldQty();
    }

    // Total number of sale records
    public function saleCount()
    {
        return $this->saleItems()->count();
    }
}
