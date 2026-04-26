<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $recordTitleAttribute = 'location';

    protected static ?string $title = 'Stok per Lokasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('location')
                    ->label('Lokasi'),

                TextInput::make('box')
                    ->label('Box'),

                TextInput::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->required()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location')->label('Lokasi')->sortable(),
                TextColumn::make('box')->label('Box')->sortable(),
                TextColumn::make('qty')->label('Qty')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Stok')
                    ->after(function ($record) {
                        Transaction::create([
                            'kode_barang' => $record->kode_barang,
                            'qty'         => $record->qty,
                            'location'    => $record->location,
                            'box'         => $record->box,
                            'type'        => 'ADJUSTMENT',
                            'status'      => 'OK',
                            'remarks'     => 'Stock Adjustment - Tambah Stok',
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        Transaction::create([
                            'kode_barang' => $record->kode_barang,
                            'qty'         => $record->qty,
                            'location'    => $record->location,
                            'box'         => $record->box,
                            'type'        => 'ADJUSTMENT',
                            'status'      => 'OK',
                            'remarks'     => 'Stock Adjustment - Edit Stok',
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        Transaction::create([
                            'kode_barang' => $record->kode_barang,
                            'qty'         => 0,
                            'location'    => $record->location,
                            'box'         => $record->box,
                            'type'        => 'ADJUSTMENT',
                            'status'      => 'OK',
                            'remarks'     => 'Stock Adjustment - Hapus Stok (qty sebelumnya: ' . $record->qty . ')',
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
