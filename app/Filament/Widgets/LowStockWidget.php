<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = '10 Produk Stok Paling Sedikit';

    protected function getTableQuery(): Builder
    {
        return Stock::query()
            ->selectRaw('kode_barang, SUM(qty) as total_qty')
            ->groupBy('kode_barang')
            ->orderByRaw('SUM(qty) ASC')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_barang')->label('Kode Barang'),
            TextColumn::make('product.nama_barang')->label('Nama Barang'),
            TextColumn::make('product.brand')->label('Brand'),
            TextColumn::make('product.colour')->label('Colour'),
            TextColumn::make('product.size')->label('Size'),
            TextColumn::make('total_qty')->label('Total Qty'),
        ];
    }

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->kode_barang;
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }
}
