<?php

namespace App\Listeners;

use App\Events\OrderFinished;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateStockAndNotify implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderFinished $event): void
    {
        $order = $event->order;

        foreach($order->items as $item){
            $product = $item->product;

            if($product && $product->stock){
                $product->stock->quantity -= $item->quantity;
                $product->stock->save();

                if($product->stock->quantity < 5){
                    logger("⚠️ Estoque baixo para {$product->name}: {$product->stock->quantity}");
                }
            }
        }
    }
}
