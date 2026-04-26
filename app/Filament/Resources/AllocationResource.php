<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllocationResource\Pages;
use App\Imports\AllocationItemPreviewImport;
use App\Models\Allocation;
use App\Models\Product;
use App\Models\Stock;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Maatwebsite\Excel\Facades\Excel;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-list';
    protected static ?string $navigationLabel = 'Allocation';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('session_id')
                    ->label('Session ID')
                    ->disabled()
                    ->dehydrated(true)
                    ->default(fn() => (string) now()->timestamp),

                Select::make('status')
                    ->options([
                        'DRAFT'     => 'Draft',
                        'CONFIRMED' => 'Confirmed',
                        'PROCESSED' => 'Processed',
                    ])
                    ->default('DRAFT')
                    ->required()
                    ->disabled(fn($record) => $record?->status === 'PROCESSED'),

                Textarea::make('remarks')
                    ->columnSpan('full'),

                FileUpload::make('items_file')
                    ->label('Upload Excel (opsional — kolom: kode_barang, qty, location, box)')
                    ->disk('public')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->reactive()
                    ->dehydrated(false)
                    ->columnSpan('full')
                    ->visible(fn($record) => $record?->status !== 'PROCESSED')
                    ->afterStateUpdated(function ($state, $livewire) {
                        if (! $state) return;
                        ini_set('memory_limit', '512M');
                        $import = new AllocationItemPreviewImport();
                        Excel::import($import, $state->getRealPath());
                        $livewire->allocationRows = $import->rows;
                    }),

                View::make('filament.allocation-items-table')
                    ->columnSpan('full')
                    ->viewData([
                        'productMap' => Product::orderBy('kode_barang')
                            ->pluck('nama_barang', 'kode_barang')
                            ->toArray(),
                        'stockMap' => Stock::select('kode_barang', 'location', 'box')
                            ->orderByDesc('qty')
                            ->get()
                            ->unique('kode_barang')
                            ->mapWithKeys(fn($s) => [
                                $s->kode_barang => ['location' => $s->location, 'box' => $s->box],
                            ])
                            ->toArray(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_id')->label('Session ID')->searchable(),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'DRAFT',
                        'warning'   => 'CONFIRMED',
                        'success'   => 'PROCESSED',
                    ]),

                TextColumn::make('items_count')
                    ->label('Jumlah Produk')
                    ->counts('items'),

                TextColumn::make('remarks')->label('Keterangan')->limit(40),

                TextColumn::make('created_at')->label('Tanggal')->date('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(Allocation $record) => $record->status === 'DRAFT')
                    ->action(fn(Allocation $record) => $record->update(['status' => 'CONFIRMED']))
                    ->successNotificationTitle('Allocation dikonfirmasi'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(Allocation $record) => $record->status !== 'PROCESSED'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAllocations::route('/'),
            'create' => Pages\CreateAllocation::route('/create'),
            'edit'   => Pages\EditAllocation::route('/{record}/edit'),
        ];
    }
}
