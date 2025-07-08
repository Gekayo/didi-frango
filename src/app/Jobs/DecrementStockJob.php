<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DecrementStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
       // Busca pedidos com status 'finished' que ainda não foram processados
        $orders = Order::where('status', 'finished')
                      ->where('stock_updated', false)
                      ->with('items') // Assumindo que há um relacionamento items
                      ->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                // Decrementa o estoque para cada item do pedido
                Stock::where('product_id', $item->product_id)
                    ->decrement('quantity', $item->quantity);
                
                // Opcional: registrar o movimento de estoque
                // StockMovement::create([...]);
            }

            // Marca o pedido como processado para evitar reprocessamento
            $order->update(['stock_updated' => true]);
        }
    }
}
