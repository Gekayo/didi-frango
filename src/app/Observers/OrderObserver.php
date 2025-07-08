<?php

namespace App\Observers;

use App\Models\Order;
use App\Jobs\DecrementStockJob;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        if ($order->status === 'finished' && !$order->stock_updated) {
            DecrementStockJob::dispatch($order);
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->isDirty('status') && 
            $order->status === 'finished' && 
            !$order->stock_updated) {
            
            Log::info("Disparando job para atualizar estoque do pedido ID: {$order->id}");
            
            // Dispara o job para processar em background
            DecrementStockJob::dispatch($order);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
