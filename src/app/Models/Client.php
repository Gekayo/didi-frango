<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'whatsapp',
        'address'
    ];

    public function order(){
        return $this->hasMany(Order::class);
    }
}
