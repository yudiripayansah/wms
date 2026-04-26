<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use App\Models\AllocationItem;
use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAllocation extends EditRecord
{
    protected static string $resource = AllocationResource::class;

    public array $allocationRows = [];

    public function mount($record): void
    {
        parent::mount($record);

        $this->allocationRows = $this->record->items()
            ->with('product')
            ->get()
            ->map(fn($item) => [
                'kode_barang' => $item->kode_barang,
                'nama_barang' => $item->product?->nama_barang ?? '',
                'qty'         => $item->qty,
                'location'    => $item->location ?? '',
                'box'         => $item->box ?? '',
            ])
            ->toArray();
    }

    protected function afterSave(): void
    {
        $this->record->items()->delete();

        foreach ($this->allocationRows as $row) {
            if (empty($row['kode_barang'])) continue;

            AllocationItem::create([
                'allocation_id' => $this->record->id,
                'kode_barang'   => $row['kode_barang'],
                'qty'           => (int) ($row['qty'] ?? 0),
                'location'      => $row['location'] ?? null,
                'box'           => $row['box'] ?? null,
            ]);
        }
    }

    protected function getActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn() => $this->record->status !== 'PROCESSED'),
        ];
    }
}
