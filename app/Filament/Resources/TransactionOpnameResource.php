<?php

namespace App\Filament\Resources;

use App\Exports\TransactionExport;
use App\Filament\Resources\TransactionOpnameResource\Pages;
use App\Models\Inventory;
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
    protected static ?string $slug = 'stock-opname';

    public static function getNavigationLabel(): string
    {
        return __('transactions.opname');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.transactions');
    }

    public static function canViewAny(): bool
    {
        return ! (current_user()?->isAllocator() ?? true);
    }

    public static function canCreate(): bool
    {
        return ! (current_user()?->isAllocator() ?? true);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return current_user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return current_user()?->isSuperAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return current_user()?->isSuperAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'OPNAME');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('session_id')
                    ->label(__('general.session_id')),

                Select::make('barcode')
                    ->label(__('general.barcode'))
                    ->options(fn() => Inventory::orderBy('barcode')->pluck('barcode', 'barcode'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateHydrated(function (?string $state, Set $set) {
                        if (! $state) return;
                        $inv = Inventory::where('barcode', $state)->first();
                        if ($inv) {
                            $set('article', $inv->article);
                            $set('color', $inv->color);
                            $set('size', $inv->size);
                        }
                    })
                    ->afterStateUpdated(function (?string $state, Set $set) {
                        $inv = $state ? Inventory::where('barcode', $state)->first() : null;
                        $set('article', $inv?->article);
                        $set('color', $inv?->color);
                        $set('size', $inv?->size);
                    }),

                TextInput::make('article')->label(__('general.article'))->disabled()->dehydrated(false),
                TextInput::make('color')->label(__('general.color'))->disabled()->dehydrated(false),
                TextInput::make('size')->label(__('general.size'))->disabled()->dehydrated(false),

                TextInput::make('qty')->label(__('general.qty'))->numeric()->required(),
                TextInput::make('location')->label(__('general.location')),
                TextInput::make('bin')->label(__('general.bin')),

                Select::make('status')
                    ->label(__('general.status'))
                    ->options(['OK' => 'OK', 'DECLINED' => 'DECLINED'])
                    ->default('OK'),

                Select::make('type')->default('OPNAME')->hidden(),

                Textarea::make('remarks')->label(__('general.remarks')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_id')->label(__('general.session_id')),
                TextColumn::make('inventory.barcode')->label(__('general.barcode')),
                TextColumn::make('inventory.article')->label(__('general.article')),
                TextColumn::make('inventory.color')->label(__('general.color')),
                TextColumn::make('inventory.size')->label(__('general.size')),
                TextColumn::make('qty')->label(__('general.qty')),
                TextColumn::make('bin')->label(__('general.bin')),
                TextColumn::make('location')->label(__('general.location')),
                TextColumn::make('type')->label(__('general.type')),
                TextColumn::make('status')->label(__('general.status')),
                TextColumn::make('created_at')->label(__('transactions.date'))->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('session_id')
                    ->label(__('general.session_id'))
                    ->options(fn() => Transaction::where('type', 'OPNAME')
                        ->whereNotNull('session_id')
                        ->where('session_id', '!=', '')
                        ->select('session_id')
                        ->distinct()
                        ->orderBy('session_id')
                        ->pluck('session_id', 'session_id')
                        ->toArray())
                    ->searchable()
                    ->placeholder(__('transactions.search_session')),

                Filter::make('dari')
                    ->form([DatePicker::make('dari')->label(__('general.from_date'))->displayFormat('d/m/Y')])
                    ->query(fn(Builder $query, array $data) =>
                        $query->when($data['dari'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v)))
                    ->indicateUsing(fn(array $data) =>
                        filled($data['dari'] ?? null)
                            ? __('transactions.filter_from', ['date' => \Carbon\Carbon::parse($data['dari'])->format('d/m/Y')])
                            : null),

                Filter::make('sampai')
                    ->form([DatePicker::make('sampai')->label(__('general.to_date'))->displayFormat('d/m/Y')])
                    ->query(fn(Builder $query, array $data) =>
                        $query->when($data['sampai'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v)))
                    ->indicateUsing(fn(array $data) =>
                        filled($data['sampai'] ?? null)
                            ? __('transactions.filter_to', ['date' => \Carbon\Carbon::parse($data['sampai'])->format('d/m/Y')])
                            : null),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->headerActions([
                Action::make('export_excel')
                    ->label(__('general.export_excel'))
                    ->action(fn() => Excel::download(new TransactionExport('OPNAME'), 'stock-opname.xlsx')),

                Action::make('export_pdf')
                    ->label(__('general.export_pdf'))
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
