<?php

namespace App\Filament\Resources\TransactionOpnameResource\Pages;

use App\Filament\Resources\TransactionOpnameResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionOpnameResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
