<?php

namespace App\Filament\Resources\TransactionInResource\Pages;

use App\Filament\Resources\TransactionInResource;
use App\Models\Stock;
use Filament\Pages\Actions;
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

        $stock = Stock::where('kode_barang', $record->kode_barang)
            ->where('location', $record->location)
            ->where('box', $record->box)
            ->first();

        if ($stock) {
            $stock->increment('qty', $record->qty);
        } else {
            Stock::create([
                'kode_barang' => $record->kode_barang,
                'qty'         => $record->qty,
                'location'    => $record->location,
                'box'         => $record->box,
            ]);
        }
    }
}
