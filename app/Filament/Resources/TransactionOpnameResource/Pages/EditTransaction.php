<?php

namespace App\Filament\Resources\TransactionOpnameResource\Pages;

use App\Filament\Resources\TransactionOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
