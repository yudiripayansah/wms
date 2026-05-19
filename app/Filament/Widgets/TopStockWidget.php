<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopStockWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('dashboard.top_stock');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::query()
                    ->selectRaw('barcode, SUM(qty) as total_qty')
                    ->groupBy('barcode')
                    ->orderByRaw('SUM(qty) DESC')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('barcode')->label(__('general.barcode')),
                TextColumn::make('inventory.article')->label(__('general.article')),
                TextColumn::make('inventory.brand')->label(__('general.brand')),
                TextColumn::make('inventory.color')->label(__('general.color')),
                TextColumn::make('inventory.size')->label(__('general.size')),
                TextColumn::make('total_qty')->label(__('inventory.total_qty')),
            ])
            ->paginated(false);
    }

    public function getTableRecordKey(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->barcode;
    }
}
