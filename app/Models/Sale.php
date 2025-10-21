<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = [];

     public function shop(){
        return $this->belongsTo(Shop::class,'shop_id');
    }

    public function customer(){
        return $this->belongsTo(Customer::class,'customer_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class,'sale_id');
    }


}
