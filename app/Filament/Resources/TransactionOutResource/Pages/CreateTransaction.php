<?php

namespace App\Filament\Resources\TransactionOutResource\Pages;

use App\Filament\Resources\TransactionOutResource;
use App\Models\Stock;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionOutResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->status !== 'OK') {
            return;
        }

        $stock = Stock::where('barcode', $record->barcode)
            ->where('location', $record->location)
            ->where('bin', $record->bin)
            ->first();

        if ($stock) {
            $stock->decrement('qty', $record->qty);
        }
    }
}
