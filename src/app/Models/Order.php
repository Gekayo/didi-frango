<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Attributes\AsModelObserver;

#[AsModelObserver(observer: \App\Observers\OrderObserver::class)]
class Order extends Model
{
    protected $fillable = [
        'client_id',
        'status',
        'type',
        'total',
        'observation'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function finalizeOrder(): void
    {
        $this->loadMissing('items.product.stock');

        foreach ($this->items as $item) {
            $stock = $item->product->stock;
            if (!$stock) {
                Log::warning("Produto sem estoque: {$item->product->name}");
                continue;
            }

            if (!$stock || $stock->quantity < $item->quantity) {
                throw ValidationException::withMessages([
                    'items' => "Estoque insuficiente para o produto: {$item->product->name}",
                ]);
            }

            $stock->decrement('quantity', $item->quantity);
        }
    }

    public function calculateTotal(): float
    {
        return $this->items->sum(fn($item) => $item->price_unity * $item->quantity);
    }
}

