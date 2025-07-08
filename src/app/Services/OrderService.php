<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function syncStockFromFinishedOrders(): void
    {
        $orders = Order::with('items.product.stock')
            ->where('status', 'finished')
            ->where('stock_synced', false)
            ->get();

        DB::transaction(function () use ($orders) {
            foreach ($orders as $order) {
                foreach ($order->items as $item) {
                    $stock = $item->product->stock;

                    if ($stock && $stock->quantity >= $item->quantity) {
                        $stock->decrement('quantity', $item->quantity);
                    }
                }

                $order->update(['stock_synced' => true]);
            }
        });
    }
}