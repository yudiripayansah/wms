<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Models\Transaction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $recordTitleAttribute = 'location';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('inventory.stocks_title');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('location')->label(__('general.location')),
                TextInput::make('bin')->label(__('general.bin')),
                TextInput::make('qty')->label(__('general.qty'))->numeric()->required()->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bin')->label(__('general.bin'))->sortable(),
                TextColumn::make('location')->label(__('general.location'))->sortable(),
                TextColumn::make('qty')->label(__('general.qty'))->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('inventory.add_stock'))
                    ->after(function ($record) {
                        Transaction::create([
                            'barcode'  => $record->barcode,
                            'qty'      => $record->qty,
                            'location' => $record->location,
                            'bin'      => $record->bin,
                            'type'     => 'ADJUSTMENT',
                            'status'   => 'OK',
                            'remarks'  => __('inventory.add_note'),
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        Transaction::create([
                            'barcode'  => $record->barcode,
                            'qty'      => $record->qty,
                            'location' => $record->location,
                            'bin'      => $record->bin,
                            'type'     => 'ADJUSTMENT',
                            'status'   => 'OK',
                            'remarks'  => __('inventory.edit_note'),
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        Transaction::create([
                            'barcode'  => $record->barcode,
                            'qty'      => 0,
                            'location' => $record->location,
                            'bin'      => $record->bin,
                            'type'     => 'ADJUSTMENT',
                            'status'   => 'OK',
                            'remarks'  => __('inventory.delete_note', ['qty' => $record->qty]),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
