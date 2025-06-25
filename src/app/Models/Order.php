<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    protected $fillable = [
        'client_id',
        'status',
        'type',
        'total',
        'observation',
    ];

   protected static function booted(){
    static::updated(function (Order $order) {
        if ($order->isDirty('status') && $order->status === 'finished') {
            event(new \App\Events\OrderFinished($order));
        }
    });
}


    public function client(){
        return $this->belongsTo(Client::class);
    }

    public function items(){
        return $this->hasMany(Order_item::class);
    }
}
