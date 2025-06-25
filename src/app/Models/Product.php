<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'is_active'
    ];

    public function orderitem(){
        return $this->hasMany(Order_item::class);
    }

    public function stock(){
        return $this->hasOne(Stock::class);
    }
}
