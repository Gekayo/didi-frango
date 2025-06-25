<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Order;

class AdjustStockForOrder implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        foreach($this->order->items as $item){
            $stock = $item->product?->stock;
            if($stock){
                $stock->decrement('quantity', $item->quantity);

                //notificar estoque baixo
                if($stock->quantity <= 5){
                    logger("⚠️ Estoque baixo: {$item->product->name} ({$stock->quantity} unidades restantes)");
                }
            }
        }
    }
}
