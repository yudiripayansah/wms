<?php

namespace App\Filament\Resources\TransactionOutResource\Pages;

use App\Filament\Resources\TransactionOutResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionOutResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
