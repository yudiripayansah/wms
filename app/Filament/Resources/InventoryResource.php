<?php

namespace App\Filament\Resources;

use App\Exports\InventoriesExport;
use App\Filament\Pages\InventoryTransactionHistory;
use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers\StocksRelationManager;
use App\Imports\InventoriesImport;
use App\Models\Inventory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
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

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getNavigationLabel(): string
    {
        return __('navigation.inventory');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.master_data');
    }

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
                Section::make(__('inventory.information'))
                    ->schema([
                        TextInput::make('barcode')
                            ->label(__('inventory.barcode'))
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('sku')
                            ->label(__('inventory.sku')),

                        TextInput::make('brand')
                            ->label(__('inventory.brand')),

                        TextInput::make('article')
                            ->label(__('inventory.article')),

                        TextInput::make('color')
                            ->label(__('inventory.color')),

                        TextInput::make('size')
                            ->label(__('inventory.size')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('barcode')
                    ->label(__('inventory.barcode'))
                    ->searchable(),
                TextColumn::make('brand')
                    ->label(__('inventory.brand'))
                    ->searchable(),
                TextColumn::make('sku')
                    ->label(__('inventory.sku'))
                    ->searchable(),
                TextColumn::make('article')
                    ->label(__('inventory.article'))
                    ->searchable(),
                TextColumn::make('color')
                    ->label(__('inventory.color')),
                TextColumn::make('size')
                    ->label(__('inventory.size')),
                TextColumn::make('stocks_sum_qty')
                    ->label(__('inventory.total_qty'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('inventory.created'))
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Action::make('histori')
                    ->label(__('inventory.history'))
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn(Inventory $record) => InventoryTransactionHistory::getUrl() . '?barcode=' . urlencode($record->barcode)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->headerActions([
                Action::make('export_excel')
                    ->label(__('general.export_excel'))
                    ->action(function () {
                        return Excel::download(new InventoriesExport(), 'inventories.xlsx');
                    }),

                Action::make('export_pdf')
                    ->label(__('general.export_pdf'))
                    ->url(fn() => url('/export-inventories-pdf'))
                    ->openUrlInNewTab(),

                Action::make('import_excel')
                    ->label(__('general.import_excel'))
                    ->form([
                        FileUpload::make('file')
                            ->required()
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ]),
                    ])
                    ->action(function (array $data) {
                        $import = new InventoriesImport();
                        Excel::import($import, storage_path('app/public/' . $data['file']));

                        Notification::make()
                            ->title(__('inventory.import_done'))
                            ->body(__('inventory.import_summary', [
                                'insert' => $import->success,
                                'update' => $import->updated,
                                'failed' => $import->failed,
                            ]))
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
            'index'  => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit'   => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
}
