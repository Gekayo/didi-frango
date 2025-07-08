<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class OrderService extends ServiceProvider
{

    public function finalizeOrder(Order $order): void{
        DB::transaction(function () use ($order){
            foreach($order->items as $item){
                $stock = $item->product->stock;

                if(!$stock || $stock->quantity < $item->quantity){
                    throw ValidationException::withMessages([
                        'items' => "Estoque insuficiente para o produto {$item->product->name}"
                    ]);

                    $stock->decrement('quantity', $item->quantity);
                }
            }
        });
    }
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
