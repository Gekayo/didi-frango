<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DecrementStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
         if ($this->order->status !== 'finished' || $this->order->stock_updated) {
            Log::info("Pedido ID: {$this->order->id} não requer atualização de estoque");
            return;
        }

        DB::transaction(function () {
            try {
                $this->order->finalizeOrder();
                $this->order->update(['stock_updated' => true]);
                
                Log::info("Estoque atualizado para o pedido ID: {$this->order->id}");

            } catch (\Exception $e) {
                Log::error("Erro ao processar pedido ID: {$this->order->id} - " . $e->getMessage());
                throw $e;
            }
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Falha ao processar estoque do pedido ID: {$this->order->id} - " . $exception->getMessage());
    }
}