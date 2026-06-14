<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Concerns\HasAllocationRows;
use App\Filament\Resources\AllocationResource;
use App\Models\AllocationStatusHistory;
use App\Models\Inventory;
use App\Models\Stock;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewAllocation extends ViewRecord
{
    use HasAllocationRows;

    protected static string $resource = AllocationResource::class;

    public array $allocationRows = [];

    public function mount($record): void
    {
        parent::mount($record);

        $items    = $this->record->items()->get();
        $barcodes = $items->pluck('barcode')->toArray();

        $inventories = Inventory::whereIn('barcode', $barcodes)
            ->get(['barcode', 'article', 'sku', 'color', 'size'])
            ->keyBy('barcode');

        $this->allocationRows = $items->map(function ($item) use ($inventories) {
            $inv = $inventories->get($item->barcode);
            return [
                'barcode'   => $item->barcode,
                'article'   => $inv?->article ?? '',
                'sku'       => $inv?->sku     ?? '',
                'color'     => $inv?->color   ?? '',
                'size'      => $inv?->size    ?? '',
                'qty'       => $item->qty,
                'location'  => $item->location ?? null,
                'bin'       => $item->bin      ?? null,
                'exceed'    => false,
                'available' => 0,
            ];
        })->toArray();

        // View page is always read-only — status changes handled by header action buttons
        $this->canManageItems = false;
        $this->canEditQty     = false;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Allocator / SuperAdmin: PENDING → PROCESSING
            Action::make('confirm')
                ->label(__('allocation.confirm_action'))
                ->icon('heroicon-o-check-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn() =>
                    $this->record->status === 'PENDING'
                    && ((current_user()?->isAllocator() ?? false) || (current_user()?->isSuperAdmin() ?? false)))
                ->action(function () {
                    $this->record->update(['status' => 'PROCESSING']);
                    AllocationStatusHistory::create([
                        'allocation_id' => $this->record->id,
                        'user_id'       => auth()->id(),
                        'from_status'   => 'PENDING',
                        'to_status'     => 'PROCESSING',
                    ]);
                    Notification::make()->title(__('allocation.confirmed_notif'))->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Allocator / SuperAdmin: PROCESSING → FINISHED (with optional notes)
            Action::make('finish')
                ->label(__('allocation.finish_action'))
                ->icon('heroicon-o-flag')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('allocation.finish_action'))
                ->form([
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->placeholder('Catatan opsional...')
                        ->rows(3),
                ])
                ->visible(fn() =>
                    $this->record->status === 'PROCESSING'
                    && ((current_user()?->isAllocator() ?? false) || (current_user()?->isSuperAdmin() ?? false)))
                ->action(function (array $data) {
                    $this->record->update(['status' => 'FINISHED']);
                    AllocationStatusHistory::create([
                        'allocation_id' => $this->record->id,
                        'user_id'       => auth()->id(),
                        'from_status'   => 'PROCESSING',
                        'to_status'     => 'FINISHED',
                        'notes'         => $data['notes'] ?? null,
                    ]);
                    Notification::make()->title(__('allocation.finished_notif'))->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Admin / SuperAdmin: FINISHED → PROCESSING (revert with notes)
            Action::make('revert')
                ->label(__('allocation.revert_action'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('allocation.revert_action'))
                ->modalDescription(__('allocation.revert_confirm'))
                ->form([
                    Textarea::make('notes')
                        ->label(__('allocation.revert_notes'))
                        ->rows(3),
                ])
                ->visible(fn() =>
                    $this->record->status === 'FINISHED'
                    && ! (current_user()?->isAllocator() ?? true))
                ->action(function (array $data) {
                    $this->record->update(['status' => 'PROCESSING']);
                    AllocationStatusHistory::create([
                        'allocation_id' => $this->record->id,
                        'user_id'       => auth()->id(),
                        'from_status'   => 'FINISHED',
                        'to_status'     => 'PROCESSING',
                        'notes'         => $data['notes'] ?? null,
                    ]);
                    Notification::make()->title(__('allocation.reverted_notif'))->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // Admin / SuperAdmin: FINISHED → COMPLETED (with DB locking)
            Action::make('complete')
                ->label(__('allocation.complete_action'))
                ->icon('heroicon-o-truck')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() =>
                    $this->record->status === 'FINISHED'
                    && ! (current_user()?->isAllocator() ?? true))
                ->action(function () {
                    $now       = now()->toDateTimeString();
                    $sessionId = (string) now()->timestamp;
                    $txRecords = [];
                    $shortages = [];

                    try {
                        DB::transaction(function () use ($now, $sessionId, &$txRecords, &$shortages) {
                            $record          = $this->record->load('items');
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

                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
