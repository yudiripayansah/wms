<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllocationResource\Pages;
use App\Imports\AllocationItemPreviewImport;
use App\Models\Allocation;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class AllocationResource extends Resource
{
    protected static ?string $model = Allocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int    $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('navigation.allocation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.transactions');
    }

    public static function canViewAny(): bool
    {
        return true;
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
                    ->label(__('allocation.no_allocation'))
                    ->disabled()
                    ->dehydrated(true)
                    ->default(fn() => (string) now()->timestamp),

                Select::make('status')
                    ->label(__('general.status'))
                    ->options([
                        'PENDING'    => __('allocation.status.pending'),
                        'PROCESSING' => __('allocation.status.processing'),
                        'FINISHED'   => __('allocation.status.finished'),
                        'COMPLETED'  => __('allocation.status.completed'),
                    ])
                    ->default('PENDING')
                    ->required()
                    ->disabled(fn($record) => $record?->status === 'COMPLETED'),

                Select::make('user_id')
                    ->label(__('allocation.assign_allocator'))
                    ->options(fn() => User::where('role', 'allocator')->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->placeholder(__('allocation.not_assigned'))
                    ->visible(fn() => ! (auth()->user()?->isAllocator() ?? true)),

                Section::make(__('allocation.shipment_info'))
                    ->description(__('allocation.shipment_desc'))
                    ->collapsible()
                    ->collapsed(fn($record) => $record === null)
                    ->columnSpan('full')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('customer')
                                ->label(__('allocation.customer')),

                            TextInput::make('distribution')
                                ->label(__('allocation.distribution')),

                            TextInput::make('release_date')
                                ->label(__('allocation.release'))
                                ->placeholder('e.g. 2025 SS, 01/06/2025'),

                            TextInput::make('brand')
                                ->label(__('allocation.brand')),

                            TextInput::make('sales_associate')
                                ->label(__('allocation.sales_associate')),

                            TextInput::make('route')
                                ->label(__('allocation.route')),
                        ]),
                    ]),

                Textarea::make('remarks')
                    ->label(__('allocation.remarks'))
                    ->columnSpan('full'),

                FileUpload::make('items_file')
                    ->label(__('allocation.upload_excel'))
                    ->disk('public')
                    ->directory('imports')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->live()
                    ->dehydrated(false)
                    ->columnSpan('full')
                    ->visible(fn($record) => $record?->status !== 'COMPLETED')
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
                        'inventoryMap' => Inventory::orderBy('barcode')
                            ->pluck('article', 'barcode')
                            ->toArray(),
                        'stockMap' => Stock::select('barcode', 'location', 'bin')
                            ->orderByDesc('qty')
                            ->get()
                            ->unique('barcode')
                            ->mapWithKeys(fn($s) => [
                                $s->barcode => ['location' => $s->location, 'bin' => $s->bin],
                            ])
                            ->toArray(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_id')
                    ->label(__('allocation.no_allocation'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('customer')
                    ->label(__('allocation.customer'))
                    ->default('—')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label(__('allocation.allocator'))
                    ->default('—')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label(__('general.status'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => __('allocation.status.' . strtolower($state)))
                    ->color(fn(string $state): string => match ($state) {
                        'PENDING'    => 'gray',
                        'PROCESSING' => 'warning',
                        'FINISHED'   => 'info',
                        'COMPLETED'  => 'success',
                        default      => 'gray',
                    }),

                TextColumn::make('items_count')
                    ->label(__('general.items'))
                    ->counts('items'),

                TextColumn::make('remarks')
                    ->label(__('general.remarks'))
                    ->limit(30),

                TextColumn::make('created_at')
                    ->label(__('general.date'))
                    ->date('d/m/Y'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip(__('general.view'))
                    ->visible(fn(Allocation $record) => auth()->user()?->isAllocator() ?? false),

                Tables\Actions\Action::make('preview_location')
                    ->iconButton()
                    ->tooltip(__('allocation.preview_location'))
                    ->icon('heroicon-o-map-pin')
                    ->color('info')
                    ->url(fn(Allocation $record) => route('allocation.preview', [$record, 'location']))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('preview_barcode')
                    ->iconButton()
                    ->tooltip(__('allocation.preview_barcode'))
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->url(fn(Allocation $record) => route('allocation.preview', [$record, 'barcode']))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('confirm')
                    ->iconButton()
                    ->tooltip(__('allocation.confirm_action'))
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn(Allocation $record) =>
                        $record->status === 'PENDING' && ! (auth()->user()?->isAllocator() ?? true))
                    ->action(fn(Allocation $record) => $record->update(['status' => 'PROCESSING']))
                    ->successNotificationTitle(__('allocation.confirmed_notif')),

                Tables\Actions\Action::make('finish')
                    ->iconButton()
                    ->tooltip(__('allocation.finish_action'))
                    ->icon('heroicon-o-flag')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn(Allocation $record) =>
                        $record->status === 'PROCESSING' && ! (auth()->user()?->isAllocator() ?? true))
                    ->action(fn(Allocation $record) => $record->update(['status' => 'FINISHED']))
                    ->successNotificationTitle(__('allocation.finished_notif')),

                Tables\Actions\Action::make('complete')
                    ->iconButton()
                    ->tooltip(__('allocation.complete_action'))
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Allocation $record) =>
                        $record->status === 'FINISHED' && ! (auth()->user()?->isAllocator() ?? true))
                    ->action(function (Allocation $record) {
                        $allocation = $record->load('items');
                        $sessionId  = now()->timestamp;

                        foreach ($allocation->items as $item) {
                            Transaction::create([
                                'session_id' => $sessionId,
                                'barcode'    => $item->barcode,
                                'qty'        => $item->qty,
                                'location'   => $item->location,
                                'bin'        => $item->bin,
                                'type'       => 'OUT',
                                'status'     => 'OK',
                                'remarks'    => 'Allocation: ' . $allocation->session_id,
                            ]);

                            $stock = Stock::where('barcode', $item->barcode)
                                ->where('location', $item->location)
                                ->where('bin', $item->bin)
                                ->first();

                            if ($stock) {
                                $stock->decrement('qty', $item->qty);
                            }
                        }

                        $record->update(['status' => 'COMPLETED']);

                        Notification::make()
                            ->title(__('allocation.completed_notif'))
                            ->body(__('transactions.items_recorded', ['count' => $allocation->items->count()]))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip(__('general.edit'))
                    ->visible(fn() => ! (auth()->user()?->isAllocator() ?? true)),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip(__('general.delete'))
                    ->visible(fn(Allocation $record) =>
                        $record->status !== 'COMPLETED' && (auth()->user()?->isSuperAdmin() ?? false)),
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
