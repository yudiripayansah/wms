<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use App\Models\AllocationItem;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAllocation extends EditRecord
{
    protected static string $resource = AllocationResource::class;

    public array $allocationRows = [];

    public function mount($record): void
    {
        parent::mount($record);

        $this->allocationRows = $this->record->items()
            ->with('inventory')
            ->get()
            ->map(fn($item) => [
                'barcode'  => $item->barcode,
                'article'  => $item->inventory?->article ?? '',
                'qty'      => $item->qty,
                'location' => $item->location ?? '',
                'bin'      => $item->bin ?? '',
            ])
            ->toArray();
    }

    protected function afterSave(): void
    {
        $this->record->items()->delete();

        foreach ($this->allocationRows as $row) {
            if (empty($row['barcode'])) continue;

            AllocationItem::create([
                'allocation_id' => $this->record->id,
                'barcode'       => $row['barcode'],
                'qty'           => (int) ($row['qty'] ?? 0),
                'location'      => $row['location'] ?? null,
                'bin'           => $row['bin'] ?? null,
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
