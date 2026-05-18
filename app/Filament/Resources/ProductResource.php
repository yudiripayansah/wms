<?php

namespace App\Filament\Resources;

use App\Exports\ProductsExport;
use App\Filament\Pages\ProductTransactionHistory;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\StocksRelationManager;
use App\Imports\ProductsImport;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Master Produk';
    protected static ?string $navigationGroup = 'Master Data';

    public static function canViewAny(): bool
    {
        return ! (auth()->user()?->isAllocator() ?? true);
    }

    public static function canCreate(): bool
    {
        return ! (auth()->user()?->isAllocator() ?? true);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withSum('stocks', 'qty');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('kode_barang')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('brand'),

                TextInput::make('barcode'),

                TextInput::make('sku'),

                TextInput::make('nama_barang')
                    ->required(),

                TextInput::make('colour'),

                TextInput::make('size'),

                TextInput::make('price')
                    ->numeric()
                    ->prefix('Rp'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_barang')->searchable(),
                TextColumn::make('nama_barang')->searchable(),
                TextColumn::make('brand'),
                TextColumn::make('sku'),
                TextColumn::make('colour'),
                TextColumn::make('size'),
                TextColumn::make('stocks_sum_qty')
                    ->label('Total Qty')
                    ->sortable(),
                TextColumn::make('price')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.')),
            ])
            ->filters([])
            ->actions([
                Action::make('histori')
                    ->label('Histori')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn(Product $record) => ProductTransactionHistory::getUrl() . '?kode_barang=' . urlencode($record->kode_barang)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->action(function () {
                        return Excel::download(new ProductsExport(), 'products.xlsx');
                    }),

                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->url(fn() => url('/export-products-pdf'))
                    ->openUrlInNewTab(),

                Action::make('import_excel')
                    ->label('Import Excel')
                    ->form([
                        FileUpload::make('file')
                            ->required()
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ]),
                    ])
                    ->action(function (array $data) {
                        $import = new ProductsImport();
                        Excel::import($import, storage_path('app/public/' . $data['file']));

                        Notification::make()
                            ->title('Import Selesai')
                            ->body(
                                "Insert: {$import->success}\n" .
                                "Update: {$import->updated}\n" .
                                "Failed: {$import->failed}"
                            )
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
