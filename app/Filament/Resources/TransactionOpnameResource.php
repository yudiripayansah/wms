<?php

namespace App\Filament\Resources;

use App\Exports\TransactionExport;
use App\Filament\Resources\TransactionOpnameResource\Pages;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class TransactionOpnameResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Stock Opname';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $slug = 'stock-opname';

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
        return parent::getEloquentQuery()->where('type', 'OPNAME');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('session_id'),

                Select::make('kode_barang')
                    ->label('Kode Barang')
                    ->options(fn() => Product::orderBy('kode_barang')->pluck('kode_barang', 'kode_barang'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateHydrated(function (?string $state, Set $set) {
                        if (! $state) return;
                        $product = Product::where('kode_barang', $state)->first();
                        if ($product) {
                            $set('nama_barang', $product->nama_barang);
                            $set('colour', $product->colour);
                            $set('size', $product->size);
                        }
                    })
                    ->afterStateUpdated(function (?string $state, Set $set) {
                        $product = $state ? Product::where('kode_barang', $state)->first() : null;
                        $set('nama_barang', $product?->nama_barang);
                        $set('colour', $product?->colour);
                        $set('size', $product?->size);
                    }),

                TextInput::make('nama_barang')->disabled()->dehydrated(false),
                TextInput::make('colour')->disabled()->dehydrated(false),
                TextInput::make('size')->disabled()->dehydrated(false),

                TextInput::make('qty')->numeric()->required(),
                TextInput::make('location'),
                TextInput::make('box'),

                Select::make('status')
                    ->options(['OK' => 'OK', 'DECLINED' => 'DECLINED'])
                    ->default('OK'),

                Select::make('type')->default('OPNAME')->hidden(),

                Textarea::make('remarks'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_id'),
                TextColumn::make('product.kode_barang')->label('Kode'),
                TextColumn::make('product.nama_barang')->label('Nama'),
                TextColumn::make('product.colour'),
                TextColumn::make('product.size'),
                TextColumn::make('qty'),
                TextColumn::make('location'),
                TextColumn::make('box'),
                TextColumn::make('type'),
                TextColumn::make('status'),
                TextColumn::make('created_at')->label('Tanggal')->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('session_id')
                    ->label('Session ID')
                    ->options(fn() => Transaction::where('type', 'OPNAME')
                        ->whereNotNull('session_id')
                        ->where('session_id', '!=', '')
                        ->select('session_id')
                        ->distinct()
                        ->orderBy('session_id')
                        ->pluck('session_id', 'session_id')
                        ->toArray())
                    ->searchable()
                    ->placeholder('Cari session ID...'),

                Filter::make('dari')
                    ->form([DatePicker::make('dari')->label('Dari')->displayFormat('d/m/Y')])
                    ->query(fn(Builder $query, array $data) =>
                        $query->when($data['dari'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v)))
                    ->indicateUsing(fn(array $data) =>
                        filled($data['dari'] ?? null) ? 'Dari: ' . \Carbon\Carbon::parse($data['dari'])->format('d/m/Y') : null),

                Filter::make('sampai')
                    ->form([DatePicker::make('sampai')->label('Sampai')->displayFormat('d/m/Y')])
                    ->query(fn(Builder $query, array $data) =>
                        $query->when($data['sampai'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v)))
                    ->indicateUsing(fn(array $data) =>
                        filled($data['sampai'] ?? null) ? 'Sampai: ' . \Carbon\Carbon::parse($data['sampai'])->format('d/m/Y') : null),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->action(fn() => Excel::download(new TransactionExport('OPNAME'), 'stock-opname.xlsx')),

                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->url(fn() => url('/export-transactions-pdf/OPNAME'))
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
        ];
    }
}
