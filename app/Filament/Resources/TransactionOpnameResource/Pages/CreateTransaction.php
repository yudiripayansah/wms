<?php

namespace App\Filament\Resources\TransactionOpnameResource\Pages;

use App\Filament\Resources\TransactionOpnameResource;
use App\Models\Stock;
use Filament\Pages\Actions;
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

        // OPNAME: set stok ke nilai persis
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
