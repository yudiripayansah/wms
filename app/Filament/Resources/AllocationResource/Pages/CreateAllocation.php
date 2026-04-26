<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use App\Models\AllocationItem;
use Filament\Resources\Pages\CreateRecord;

class CreateAllocation extends CreateRecord
{
    protected static string $resource = AllocationResource::class;

    public array $allocationRows = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['session_id'] = (string) now()->timestamp;
        return $data;
    }

    protected function afterCreate(): void
    {
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
}
