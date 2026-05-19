<?php

namespace App\Filament\Resources\TransactionOpnameResource\Pages;

use App\Filament\Resources\TransactionOpnameResource;
use App\Models\Stock;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionOpnameResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->status !== 'OK') {
            return;
        }

        Stock::updateOrCreate(
            [
                'barcode'  => $record->barcode,
                'location' => $record->location,
                'bin'      => $record->bin,
            ],
            ['qty' => $record->qty]
        );
    }
}
