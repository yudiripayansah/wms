<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllocationResource\Pages;
use App\Imports\AllocationItemPreviewImport;
use App\Models\Allocation;
use App\Models\AllocationStatusHistory;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
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
        return ! (current_user()?->isAllocator() ?? true);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        if (in_array($record->status, ['COMPLETED', 'CANCELED'])) return false;

        $user = current_user();
        if ($user?->isSuperAdmin()) return true;
        if ($user?->isAllocator()) return $record->status === 'PROCESSING';
        return $record->status === 'PENDING';
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
        $query = parent::getEloquentQuery();

        if (current_user()?->isAllocator()) {
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
                        'CANCELED'   => __('allocation.status.canceled'),
                    ])
                    ->default('PENDING')
                    ->required()
                    ->disabled(), // status changed via actions only

                Select::make('user_id')
                    ->label(__('allocation.assign_allocator'))
                    ->options(fn() => User::where('role', 'allocator')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->placeholder(__('allocation.not_assigned'))
                    ->visible(fn() => ! (current_user()?->isAllocator() ?? true)),

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
                    ->visible(fn($record) =>
                        ! in_array($record?->status, ['PROCESSING', 'FINISHED', 'COMPLETED', 'CANCELED'])
                        && ! (current_user()?->isAllocator() ?? false))
                    ->afterStateUpdated(function ($state, $livewire) {
                        if (! $state) return;
                        set_time_limit(0);
                        ini_set('memory_limit', '512M');

                        $import = new AllocationItemPreviewImport();
                        Excel::import($import, $state->getRealPath());

                        $barcodes    = array_keys($import->accumulated);
                        $inventories = Inventory::whereIn('barcode', $barcodes)
                            ->get(['barcode', 'article', 'sku', 'color', 'size'])
                            ->keyBy('barcode');

                        $stockTotals = Stock::selectRaw('barcode, SUM(qty) as total')
                            ->whereIn('barcode', $barcodes)
                            ->groupBy('barcode')
                            ->pluck('total', 'barcode');

                        $reservedMap = DB::table('allocation_items')
                            ->join('allocations', 'allocations.id', '=', 'allocation_items.allocation_id')
                            ->whereIn('allocations.status', ['PROCESSING', 'FINISHED'])
                            ->whereIn('allocation_items.barcode', $barcodes)
                            ->selectRaw('allocation_items.barcode, SUM(allocation_items.qty) as reserved')
                            ->groupBy('allocation_items.barcode')
                            ->pluck('reserved', 'allocation_items.barcode');

                        $livewire->allocationRows = collect($import->accumulated)
                            ->map(function ($data) use ($inventories, $stockTotals, $reservedMap) {
                                $inv       = $inventories->get($data['barcode']);
                                $available = max(0, ($stockTotals[$data['barcode']] ?? 0) - ($reservedMap[$data['barcode']] ?? 0));
                                return [
                                    'barcode'   => $data['barcode'],
                                    'article'   => $inv?->article ?? '',
                                    'sku'       => $inv?->sku     ?? '',
                                    'color'     => $inv?->color   ?? '',
                                    'size'      => $inv?->size    ?? '',
                                    'qty'       => $data['qty'],
                                    'location'  => $data['location'] ?? null,
                                    'bin'       => $data['bin']      ?? null,
                                    'exceed'    => $data['qty'] > $available,
                                    'available' => $available,
                                ];
                            })
                            ->values()
                            ->toArray();

                        $livewire->totalAllocationRows = $import->totalRawRows;
                    }),

                View::make('filament.allocation-items-table')
                    ->columnSpan('full')
                    ->viewData([
                        'inventoryMap' => Inventory::orderBy('barcode')
                            ->get(['barcode', 'article', 'sku', 'color', 'size'])
                            ->keyBy('barcode')
                            ->map(fn($inv) => [
                                'article' => $inv->article,
                                'sku'     => $inv->sku,
                                'color'   => $inv->color,
                                'size'    => $inv->size,
                            ])
                            ->toArray(),
                        'stockTotals' => Stock::selectRaw('barcode, SUM(qty) as total')
                            ->groupBy('barcode')
                            ->pluck('total', 'barcode')
                            ->toArray(),
                        'reservedMap' => DB::table('allocation_items')
                            ->join('allocations', 'allocations.id', '=', 'allocation_items.allocation_id')
                            ->whereIn('allocations.status', ['PROCESSING', 'FINISHED'])
                            ->selectRaw('allocation_items.barcode, SUM(allocation_items.qty) as reserved')
                            ->groupBy('allocation_items.barcode')
                            ->pluck('reserved', 'allocation_items.barcode')
                            ->toArray(),
                        'stockBreakdown' => Stock::where('qty', '>', 0)
                            ->orderByDesc('qty')
                            ->get(['barcode', 'location', 'bin', 'qty'])
                            ->groupBy('barcode')
                            ->map(fn($g) => $g->map(fn($s) => [
                                'l' => $s->location,
                                'b' => $s->bin,
                                'q' => (int) $s->qty,
                            ])->values())
                            ->toArray(),
                    ]),

                Section::make(__('allocation.history_title'))
                    ->columnSpan('full')
                    ->schema([
                        Placeholder::make('status_history')
                            ->label('')
                            ->columnSpan('full')
                            ->content(function (?Allocation $record): HtmlString {
                                if (! $record) return new HtmlString('');
                                $histories = $record->statusHistories()->with('user')->get();
                                return new HtmlString(
                                    view('filament.allocation-status-history', ['histories' => $histories])->render()
                                );
                            }),
                    ])
                    ->visible(fn(?Allocation $record) => $record !== null),
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
                    ->visible(fn() => current_user()?->isAllocator() ?? false),

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
                        $record->status === 'PENDING'
                        && ((current_user()?->isAllocator() ?? false) || (current_user()?->isSuperAdmin() ?? false)))
                    ->action(function (Allocation $record) {
                        $record->update(['status' => 'PROCESSING']);
                        AllocationStatusHistory::create([
                            'allocation_id' => $record->id,
                            'user_id'       => auth()->id(),
                            'from_status'   => 'PENDING',
                            'to_status'     => 'PROCESSING',
                        ]);
                        Notification::make()->title(__('allocation.confirmed_notif'))->success()->send();
                    }),

                Tables\Actions\Action::make('finish')
                    ->iconButton()
                    ->tooltip(__('allocation.finish_action'))
                    ->icon('heroicon-o-flag')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('allocation.finish_action'))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Catatan opsional...')
                            ->rows(3),
                    ])
                    ->visible(fn(Allocation $record) =>
                        $record->status === 'PROCESSING'
                        && ((current_user()?->isAllocator() ?? false) || (current_user()?->isSuperAdmin() ?? false)))
                    ->action(function (Allocation $record, array $data) {
                        $record->update(['status' => 'FINISHED']);
                        AllocationStatusHistory::create([
                            'allocation_id' => $record->id,
                            'user_id'       => auth()->id(),
                            'from_status'   => 'PROCESSING',
                            'to_status'     => 'FINISHED',
                            'notes'         => $data['notes'] ?? null,
                        ]);
                        Notification::make()->title(__('allocation.finished_notif'))->success()->send();
                    }),

                Tables\Actions\Action::make('complete')
                    ->iconButton()
                    ->tooltip(__('allocation.complete_action'))
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Allocation $record) =>
                        $record->status === 'FINISHED' && ! (current_user()?->isAllocator() ?? true))
                    ->action(function (Allocation $record) {
                        $now       = now()->toDateTimeString();
                        $sessionId = (string) now()->timestamp;
                        $txRecords = [];
                        $shortages = [];

                        try {
                            DB::transaction(function () use ($record, $now, $sessionId, &$txRecords, &$shortages) {
                                $barcodes        = $record->items->pluck('barcode')->unique()->toArray();
                                $stocksByBarcode = Stock::whereIn('barcode', $barcodes)
                                    ->lockForUpdate()
                                    ->orderByDesc('qty')
                                    ->get()
                                    ->groupBy('barcode')
                                    ->map(fn($g) => $g->values());

                                foreach ($record->items as $item) {
                                    $avail = ($stocksByBarcode->get($item->barcode) ?? collect())->sum('qty');
                                    if ($avail < $item->qty) {
                                        $shortages[] = "Barcode {$item->barcode}: tersedia {$avail}, dibutuhkan {$item->qty}";
                                    }
                                }

                                if (! empty($shortages)) throw new \RuntimeException('insufficient_stock');

                                foreach ($record->items as $item) {
                                    $remaining  = (int) $item->qty;
                                    $itemStocks = $stocksByBarcode->get($item->barcode, collect());

                                    foreach ($itemStocks as $stock) {
                                        if ($remaining <= 0) break;
                                        $deduct = min($remaining, (int) $stock->qty);
                                        if ($deduct <= 0) continue;
                                        $stock->decrement('qty', $deduct);
                                        $remaining -= $deduct;
                                        $txRecords[] = [
                                            'session_id' => $sessionId,
                                            'barcode'    => $item->barcode,
                                            'qty'        => $deduct,
                                            'location'   => $stock->location,
                                            'bin'        => $stock->bin,
                                            'type'       => 'OUT',
                                            'status'     => 'OK',
                                            'remarks'    => 'Allocation: ' . $record->session_id,
                                            'created_at' => $now,
                                            'updated_at' => $now,
                                        ];
                                    }
                                }

                                foreach (array_chunk($txRecords, 500) as $chunk) {
                                    DB::table('transactions')->insert($chunk);
                                }

                                $record->update(['status' => 'COMPLETED']);

                                AllocationStatusHistory::create([
                                    'allocation_id' => $record->id,
                                    'user_id'       => auth()->id(),
                                    'from_status'   => 'FINISHED',
                                    'to_status'     => 'COMPLETED',
                                ]);
                            });
                        } catch (\RuntimeException $e) {
                            if ($e->getMessage() === 'insufficient_stock') {
                                Notification::make()
                                    ->title('Stok tidak mencukupi')
                                    ->body(implode("\n", $shortages))
                                    ->danger()->persistent()->send();
                                return;
                            }
                            throw $e;
                        }

                        Notification::make()
                            ->title(__('allocation.completed_notif'))
                            ->body(__('transactions.items_recorded', ['count' => count($txRecords)]))
                            ->success()->send();
                    }),

                Tables\Actions\Action::make('revert')
                    ->iconButton()
                    ->tooltip(__('allocation.revert_action'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('allocation.revert_action'))
                    ->modalDescription(__('allocation.revert_confirm'))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label(__('allocation.revert_notes'))
                            ->rows(3),
                    ])
                    ->visible(fn(Allocation $record) =>
                        $record->status === 'FINISHED'
                        && ! (current_user()?->isAllocator() ?? true))
                    ->action(function (Allocation $record, array $data) {
                        $record->update(['status' => 'PROCESSING']);
                        AllocationStatusHistory::create([
                            'allocation_id' => $record->id,
                            'user_id'       => auth()->id(),
                            'from_status'   => 'FINISHED',
                            'to_status'     => 'PROCESSING',
                            'notes'         => $data['notes'] ?? null,
                        ]);
                        Notification::make()->title(__('allocation.reverted_notif'))->success()->send();
                    }),

                Tables\Actions\Action::make('cancel')
                    ->iconButton()
                    ->tooltip(__('allocation.cancel_action'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(__('allocation.cancel_action'))
                    ->modalDescription(__('allocation.cancel_confirm'))
                    ->visible(fn(Allocation $record) =>
                        $record->status === 'COMPLETED' && ! (current_user()?->isAllocator() ?? true))
                    ->action(function (Allocation $record) {
                        $now       = now()->toDateTimeString();
                        $sessionId = (string) now()->timestamp;

                        DB::transaction(function () use ($record, $now, $sessionId) {
                            $outTxns  = DB::table('transactions')
                                ->where('type', 'OUT')
                                ->where('remarks', 'Allocation: ' . $record->session_id)
                                ->get();

                            $barcodes = $outTxns->pluck('barcode')->unique()->toArray();
                            $stocks   = Stock::whereIn('barcode', $barcodes)
                                ->lockForUpdate()
                                ->get()
                                ->keyBy(fn($s) => $s->barcode . '|' . ($s->location ?? '') . '|' . ($s->bin ?? ''));

                            $inRecords = [];

                            foreach ($outTxns as $txn) {
                                $key   = $txn->barcode . '|' . ($txn->location ?? '') . '|' . ($txn->bin ?? '');
                                $stock = $stocks->get($key);

                                if ($stock) {
                                    $stock->increment('qty', $txn->qty);
                                } else {
                                    Stock::create([
                                        'barcode'    => $txn->barcode,
                                        'location'   => $txn->location,
                                        'bin'        => $txn->bin,
                                        'qty'        => $txn->qty,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]);
                                }

                                $inRecords[] = [
                                    'session_id' => $sessionId,
                                    'barcode'    => $txn->barcode,
                                    'qty'        => $txn->qty,
                                    'location'   => $txn->location,
                                    'bin'        => $txn->bin,
                                    'type'       => 'IN',
                                    'status'     => 'OK',
                                    'remarks'    => 'Reversal: Allocation ' . $record->session_id,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }

                            foreach (array_chunk($inRecords, 500) as $chunk) {
                                DB::table('transactions')->insert($chunk);
                            }

                            $record->update(['status' => 'CANCELED']);

                            AllocationStatusHistory::create([
                                'allocation_id' => $record->id,
                                'user_id'       => auth()->id(),
                                'from_status'   => 'COMPLETED',
                                'to_status'     => 'CANCELED',
                            ]);
                        });

                        Notification::make()
                            ->title(__('allocation.canceled_notif'))
                            ->success()->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip(__('general.edit'))
                    ->visible(fn(Allocation $record) =>
                        ! in_array($record->status, ['COMPLETED', 'CANCELED'])
                        && (
                            (current_user()?->isSuperAdmin() ?? false)
                            || ($record->status === 'PENDING' && ! (current_user()?->isAllocator() ?? true))
                            || ($record->status === 'PROCESSING' && (current_user()?->isAllocator() ?? false))
                        )),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip(__('general.delete'))
                    ->visible(fn(Allocation $record) =>
                        $record->status !== 'COMPLETED' && (current_user()?->isSuperAdmin() ?? false)),
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
