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
                'kode_barang' => $record->kode_barang,
                'location'    => $record->location,
                'box'         => $record->box,
            ],
            ['qty' => $record->qty]
        );
    }
}
