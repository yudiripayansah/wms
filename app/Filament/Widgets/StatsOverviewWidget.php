<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalProducts = Product::count();

        $totalBrands = Product::whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct('brand')
            ->count('brand');

        $totalValue = DB::table('stocks')
            ->join('products', 'stocks.kode_barang', '=', 'products.kode_barang')
            ->sum(DB::raw('stocks.qty * products.price'));

        return [
            Stat::make('Total Produk', number_format($totalProducts, 0, ',', '.'))
                ->icon('heroicon-o-cube'),

            Stat::make('Total Brand', number_format($totalBrands, 0, ',', '.'))
                ->icon('heroicon-o-tag'),

            Stat::make('Total Nilai Stok', 'Rp ' . number_format($totalValue, 0, ',', '.'))
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
