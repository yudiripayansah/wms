<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Concerns\HasAllocationRows;
use App\Filament\Resources\AllocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAllocation extends CreateRecord
{
    use HasAllocationRows;

    protected static string $resource = AllocationResource::class;

    public array $allocationRows = [];

    public function mount(): void
    {
        parent::mount();
        $this->resolveAllocationPermissions('PENDING');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['session_id'] = (string) now()->timestamp;
        return $data;
    }

    protected function beforeCreate(): void
    {
        $this->validateRowsBeforeSave();
        $this->checkAndHaltOnStockExceed();
    }

    protected function afterCreate(): void
    {
        $this->saveAllocationItems($this->record->id);
    }
}
