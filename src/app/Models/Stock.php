<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'quantity',
        'unit',
        'minimum_alert'
    ];

    public function product()
{
    return $this->belongsTo(Product::class);
}
}
