<?php

namespace App\Filament\Resources\AllocationResource\Pages;

use App\Filament\Resources\AllocationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAllocation extends ViewRecord
{
    protected static string $resource = AllocationResource::class;

    protected function getHeaderActions(): array
    {
        return []; // read-only — tidak ada action untuk allocator
    }
}
