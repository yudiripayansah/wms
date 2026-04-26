<?php

namespace App\Filament\Resources\AllocationResource\RelationManagers;

use App\Imports\AllocationItemImport;
use App\Models\Stock;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'kode_barang';

    protected static ?string $title = 'Daftar Produk Allocation';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('kode_barang')
                    ->label('Kode Barang')
                    ->relationship('product', 'kode_barang')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateHydrated(function ($state, callable $set) {
                        if (! $state) return;
                        $product = \App\Models\Product::where('kode_barang', $state)->first();
                        if ($product) {
                            $set('nama_barang', $product->nama_barang);
                            $set('colour', $product->colour);
                            $set('size', $product->size);
                        }
                        $stock = Stock::where('kode_barang', $state)->orderByDesc('qty')->first();
                        if ($stock) {
                            $set('location', $stock->location);
                            $set('box', $stock->box);
                        }
                    })
                    ->afterStateUpdated(function ($state, callable $set) {
                        $product = \App\Models\Product::where('kode_barang', $state)->first();
                        if ($product) {
                            $set('nama_barang', $product->nama_barang);
                            $set('colour', $product->colour);
                            $set('size', $product->size);
                        } else {
                            $set('nama_barang', null);
                            $set('colour', null);
                            $set('size', null);
                        }
                        $stock = $state ? Stock::where('kode_barang', $state)->orderByDesc('qty')->first() : null;
                        $set('location', $stock?->location);
                        $set('box', $stock?->box);
                    }),

                TextInput::make('nama_barang')->disabled()->dehydrated(false),
                TextInput::make('colour')->disabled()->dehydrated(false),
                TextInput::make('size')->disabled()->dehydrated(false),

                TextInput::make('qty')
                    ->numeric()
                    ->required()
                    ->minValue(1),

                TextInput::make('location')->label('Location'),
                TextInput::make('box')->label('Box'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_barang')->label('Kode Barang'),
                TextColumn::make('product.nama_barang')->label('Nama Barang'),
                TextColumn::make('product.colour')->label('Colour'),
                TextColumn::make('product.size')->label('Size'),
                TextColumn::make('qty')->label('Qty'),
                TextColumn::make('location')->label('Location'),
                TextColumn::make('box')->label('Box'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah Produk'),

                Tables\Actions\Action::make('import_excel')
                    ->label('Import Excel')
                    ->icon('heroicon-o-upload')
                    ->color('secondary')
                    ->modalHeading('Import Produk dari Excel')
                    ->modalSubheading('Format kolom: kode_barang, qty. Kolom location & box opsional — jika kosong akan diisi otomatis dari data stok.')
                    ->modalButton('Import')
                    ->form([
                        FileUpload::make('file')
                            ->label('File Excel (.xlsx)')
                            ->disk('public')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        $path   = Storage::disk('public')->path($data['file']);
                        $import = new AllocationItemImport($livewire->ownerRecord->id);

                        Excel::import($import, $path);

                        Storage::disk('public')->delete($data['file']);

                        Notification::make()
                            ->title("{$import->imported} produk berhasil diimport" . ($import->skipped ? ", {$import->skipped} baris dilewati (kode tidak ditemukan)" : ''))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
