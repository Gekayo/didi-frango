<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price'
    ];

    public function stock(){
        return $this->hasOne(Stock::class);
    }

    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }
}
