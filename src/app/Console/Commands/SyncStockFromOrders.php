<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StockService;


class SyncStockFromOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-stock-from-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o estoque com base em pedidos finalizados';

    /**
     * Execute the console command.
     */
   public function handle(StockService $stockService): int
    {
        $stockService->syncStockFromFinishedOrders();
        $this->info('Estoque atualizado com base nos pedidos finalizados.');
        return self::SUCCESS;
    }
}
