<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order_item extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price_unity',
    ];

   protected static function booted()
    {
        static::updating(function ($item) {
            if ($item->isDirty('quantity')) {
                $originalQuantity = $item->getOriginal('quantity');
                $newQuantity = $item->quantity;
                
                if ($newQuantity > $originalQuantity) {
                    $difference = $newQuantity - $originalQuantity;
                    $stock = Stock::where('product_id', $item->product_id)->first();
                    
                    if ($stock->quantity < $difference) {
                        throw new \Exception("Estoque insuficiente para aumentar a quantidade");
                    }
                }
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
