<?php

namespace App\Filament\Resources\TransactionInResource\Pages;

use App\Filament\Resources\TransactionInResource;
use App\Models\Stock;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionInResource::class;

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
            $stock->increment('qty', $record->qty);
        } else {
            Stock::create([
                'barcode'  => $record->barcode,
                'qty'      => $record->qty,
                'location' => $record->location,
                'bin'      => $record->bin,
            ]);
        }
    }
}
