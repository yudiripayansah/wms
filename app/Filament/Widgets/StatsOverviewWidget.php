<?php

namespace App\Filament\Widgets;

use App\Models\Inventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalInventories = Inventory::count();

        $totalBrands = Inventory::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct('brand')
            ->count('brand');

        $totalQty = DB::table('stocks')->sum('qty');

        return [
            Stat::make(__('dashboard.total_inventory'), number_format($totalInventories, 0, ',', '.'))
                ->icon('heroicon-o-cube'),

            Stat::make(__('dashboard.total_brand'), number_format($totalBrands, 0, ',', '.'))
                ->icon('heroicon-o-tag'),

            Stat::make(__('dashboard.total_stock_qty'), number_format($totalQty, 0, ',', '.'))
                ->icon('heroicon-o-archive-box'),
        ];
    }
}
