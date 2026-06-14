<?php

namespace App\Concerns;

use App\Models\AllocationItem;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Renderless;

trait HasAllocationRows
{
    public int  $totalAllocationRows = 0;
    public bool $canManageItems      = true;
    public bool $canEditQty          = true;
    public bool $isSelfReserved      = false; // true when this allocation IS in reservedMap (PROCESSING/FINISHED)

    protected function resolveAllocationPermissions(?string $status = null): void
    {
        // COMPLETED and CANCELED are permanently locked — no modifications allowed
        if (in_array($status, ['COMPLETED', 'CANCELED'])) {
            $this->canManageItems = false;
            $this->canEditQty     = false;
            return;
        }

        $user = current_user();

        if ($user?->isSuperAdmin()) {
            $this->canManageItems = true;
            $this->canEditQty     = true;
            return;
        }

        if ($user?->isAllocator()) {
            $this->canManageItems = false;
            $this->canEditQty     = $status === 'PROCESSING';
            return;
        }

        // Admin
        $this->canManageItems = $status === 'PENDING';
        $this->canEditQty     = $status === 'PENDING';
    }

    #[Renderless]
    public function addAllocationRow(): void
    {
        $this->allocationRows[] = [
            'barcode'   => '',
            'article'   => '',
            'sku'       => '',
            'color'     => '',
            'size'      => '',
            'qty'       => 0,
            'exceed'    => false,
            'available' => 0,
        ];
    }

    #[Renderless]
    public function removeAllocationRow(int $index): void
    {
        array_splice($this->allocationRows, $index, 1);
        $this->allocationRows = array_values($this->allocationRows);
    }

    /**
     * Persist allocationRows to allocation_items.
     * Auto-fills location/bin from the highest-qty stock record when not provided.
     */
    protected function saveAllocationItems(int $allocationId): void
    {
        $barcodes = collect($this->allocationRows)
            ->pluck('barcode')
            ->filter()
            ->unique()
            ->toArray();

        // Single query to get best stock location per barcode (highest qty)
        $stockDefaults = Stock::whereIn('barcode', $barcodes)
            ->orderByDesc('qty')
            ->get()
            ->groupBy('barcode')
            ->map(fn($g) => $g->first());

        AllocationItem::where('allocation_id', $allocationId)->delete();

        foreach ($this->allocationRows as $row) {
            if (empty($row['barcode'])) continue;
            if ((int) ($row['qty'] ?? 0) <= 0) continue; // only save items with qty > 0

            $location = ($row['location'] ?? '') ?: $stockDefaults->get($row['barcode'])?->location;
            $bin      = ($row['bin']      ?? '') ?: $stockDefaults->get($row['barcode'])?->bin;

            AllocationItem::create([
                'allocation_id' => $allocationId,
                'barcode'       => $row['barcode'],
                'qty'           => (int) ($row['qty'] ?? 0),
                'location'      => $location ?: null,
                'bin'           => $bin      ?: null,
            ]);
        }
    }

    /**
     * Validates rows before create/save: no empty list, all-zero qty, or duplicate barcodes.
     */
    protected function validateRowsBeforeSave(): void
    {
        $validRows = collect($this->allocationRows)
            ->filter(fn($r) => ! empty($r['barcode']));

        if ($validRows->isEmpty()) {
            \Filament\Notifications\Notification::make()
                ->title('Item kosong')
                ->body('Tambahkan minimal satu item sebelum menyimpan.')
                ->danger()->send();
            $this->halt();
        }

        $hasQty = $validRows->filter(fn($r) => (int) ($r['qty'] ?? 0) > 0)->isNotEmpty();
        if (! $hasQty) {
            \Filament\Notifications\Notification::make()
                ->title('Qty tidak valid')
                ->body('Minimal satu item harus memiliki qty lebih dari 0.')
                ->danger()->send();
            $this->halt();
        }

        $barcodes = $validRows->pluck('barcode');
        if ($barcodes->count() !== $barcodes->unique()->count()) {
            \Filament\Notifications\Notification::make()
                ->title('Barcode duplikat')
                ->body('Terdapat barcode yang sama dalam daftar. Hapus item yang duplikat.')
                ->danger()->send();
            $this->halt();
        }
    }

    /**
     * Called before create/save — halts if any row exceeds available stock.
     * Excludes $excludeAllocationId from reserved-qty calculation (self-edit).
     */
    protected function checkAndHaltOnStockExceed(?int $excludeAllocationId = null): void
    {
        $barcodes = array_filter(array_column($this->allocationRows, 'barcode'));
        if (empty($barcodes)) return;

        $stockTotals = Stock::selectRaw('barcode, SUM(qty) as total')
            ->whereIn('barcode', $barcodes)
            ->groupBy('barcode')
            ->pluck('total', 'barcode');

        $reservedQuery = DB::table('allocation_items')
            ->join('allocations', 'allocations.id', '=', 'allocation_items.allocation_id')
            ->whereIn('allocations.status', ['PROCESSING', 'FINISHED'])
            ->whereIn('allocation_items.barcode', $barcodes);

        if ($excludeAllocationId) {
            $reservedQuery->where('allocations.id', '!=', $excludeAllocationId);
        }

        $reservedMap = $reservedQuery
            ->selectRaw('allocation_items.barcode, SUM(allocation_items.qty) as reserved')
            ->groupBy('allocation_items.barcode')
            ->pluck('reserved', 'allocation_items.barcode');

        $exceeded = [];
        foreach ($this->allocationRows as $row) {
            if (empty($row['barcode'])) continue;
            $available = max(0, ($stockTotals[$row['barcode']] ?? 0) - ($reservedMap[$row['barcode']] ?? 0));
            if ((int) ($row['qty'] ?? 0) > $available) {
                $exceeded[] = "{$row['barcode']} (tersedia: {$available})";
            }
        }

        if (! empty($exceeded)) {
            \Filament\Notifications\Notification::make()
                ->title('Stok tidak mencukupi')
                ->body('Item berikut melebihi stok yang tersedia: ' . implode(', ', $exceeded))
                ->danger()
                ->persistent()
                ->send();
            $this->halt();
        }
    }
}
