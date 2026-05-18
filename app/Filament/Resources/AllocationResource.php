<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\AllocationResource\Pages;
use App\Imports\AllocationItemPreviewImport;
use App\Models\Allocation;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Allocation';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int    $navigationSort  = 1;

    public static function canViewAny(): bool
    {
        return true; // semua role bisa lihat (allocator hanya lihat miliknya)
    }

    public static function canCreate(): bool
    {
        return ! (auth()->user()?->isAllocator() ?? true);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! (auth()->user()?->isAllocator() ?? true);
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
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isAllocator()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

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

                Select::make('user_id')
                    ->label('Assign ke Allocator')
                    ->options(fn() => User::where('role', 'allocator')->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->placeholder('— Tidak diassign —')
                    ->visible(fn() => ! (auth()->user()?->isAllocator() ?? true)),

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
                    ->live()
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

                TextColumn::make('user.name')
                    ->label('Allocator')
                    ->default('—')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'DRAFT'     => 'gray',
                        'CONFIRMED' => 'warning',
                        'PROCESSED' => 'success',
                        default     => 'gray',
                    }),

                TextColumn::make('items_count')
                    ->label('Jumlah Produk')
                    ->counts('items'),

                TextColumn::make('remarks')->label('Keterangan')->limit(40),

                TextColumn::make('created_at')->label('Tanggal')->date('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn(Allocation $record) => auth()->user()?->isAllocator() ?? false),

                Tables\Actions\Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(Allocation $record) => $record->status === 'DRAFT' && ! (auth()->user()?->isAllocator() ?? true))
                    ->action(fn(Allocation $record) => $record->update(['status' => 'CONFIRMED']))
                    ->successNotificationTitle('Allocation dikonfirmasi'),

                Tables\Actions\EditAction::make()
                    ->visible(fn() => ! (auth()->user()?->isAllocator() ?? true)),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn(Allocation $record) => $record->status !== 'PROCESSED' && (auth()->user()?->isSuperAdmin() ?? false)),
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
            'view'   => Pages\ViewAllocation::route('/{record}'),
        ];
    }
}
