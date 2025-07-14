<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use App\Models\Order;
use Illuminate\Support\Number;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '1s';

    protected ?string $heading = 'Analytics';

    protected ?string $description = 'Analise detalhada das vendas Diária, Semanal e Mensal.';


    protected function getStats(): array
    {
        $trendData = Trend::model(Order::class)
            ->between(now()->subDay(6), now())
            ->perDay()
            ->count();

        $chartData = $trendData->pluck('aggregate')->toArray();

        $totalOrders = Order::count();
        
        $monthlyOrders = Order::whereBetween('created_at', [now()->startOfMonth(), now()])->count();

        $weeklyOrders = Order::whereBetween('created_at', [now()->startOfWeek(), now()])->count();


        //monthly variation
        $lastMonthOrders = Order::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count();

        $monthlyChange = $lastMonthOrders > 0 ? (($monthlyOrders - $lastMonthOrders) / $lastMonthOrders * 100) : ($monthlyOrders > 0 ? 100 : 0);

        //full value
        $totalValue = Order::sum('total');
        $monthlyValue = Order::whereBetween('created_at', [now()->startOfMonth(), now()])->sum('total');

        return [
            Stat::make('Total de Pedidos', Number::format($totalOrders))
            ->description($monthlyChange > 0 ? '↑ ' . Number::percentage($monthlyChange, 1) : '↓ ' . Number::percentage(abs($monthlyChange), 1))
            ->descriptionIcon($monthlyChange > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($monthlyChange > 0 ? 'success' : 'danger')
            ->chart($chartData),

            Stat::make('Pedidos Mensais', Number::format($totalOrders))
                ->description(Number::format($weeklyOrders) . ' esta semana')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart($chartData),

            Stat::make('Valor Total', Number::currency($totalValue, 'BRL'))
                ->description(Number::currency($monthlyValue, 'BRL') . ' este mês')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->chart($this->getValueTrendChart()),
        ];
    }

    protected function getValueTrendChart(): array
    {
        $trendData = Trend::model(Order::class)
            ->between(now()->subDays(6), now())
            ->perDay()
            ->sum('total');

        return $trendData->pluck('aggregate')->toArray();
    }
}
