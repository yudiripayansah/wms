<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Concerns\HasAllocationRows;
use App\Filament\Resources\AllocationResource;
use App\Models\AllocationStatusHistory;
use App\Models\Inventory;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAllocation extends EditRecord
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

        $this->resolveAllocationPermissions($this->record->status);
        $this->isSelfReserved = in_array($this->record->status, ['PROCESSING', 'FINISHED']);
    }

    protected function beforeSave(): void
    {
        $user = current_user();

        $this->validateRowsBeforeSave();

        // SuperAdmin: no restrictions
        if ($user?->isSuperAdmin()) return;

        if ($user?->isAllocator()) {
            // Allocator: only qty changes allowed — barcodes must stay the same
            $existingBarcodes = $this->record->items()->pluck('barcode')->sort()->values()->toArray();
            $newBarcodes      = collect(array_column($this->allocationRows, 'barcode'))
                ->filter()->sort()->values()->toArray();

            if ($existingBarcodes !== $newBarcodes) {
                Notification::make()
                    ->title('Tidak diizinkan')
                    ->body('Sebagai allocator, Anda hanya bisa mengubah qty item — tidak bisa menambah atau menghapus.')
                    ->danger()
                    ->send();
                $this->halt();
            }
        }

        $this->checkAndHaltOnStockExceed(excludeAllocationId: $this->record->id);
    }

    protected function afterSave(): void
    {
        $this->saveAllocationItems($this->record->id);

        // Log qty change to history (allocator editing items)
        if (current_user()?->isAllocator()) {
            AllocationStatusHistory::create([
                'allocation_id' => $this->record->id,
                'user_id'       => auth()->id(),
                'from_status'   => $this->record->status,
                'to_status'     => $this->record->status,
                'notes'         => 'Allocator mengubah qty item.',
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => $this->record->status !== 'COMPLETED'),
        ];
    }
}
