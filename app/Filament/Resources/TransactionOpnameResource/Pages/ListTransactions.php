<?php

namespace App\Filament\Resources\TransactionOpnameResource\Pages;

use App\Filament\Resources\TransactionOpnameResource;
use App\Imports\TransactionInPreviewImport;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionOpnameResource::class;

    public array $transactionRows = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newTransaction')
                ->label(__('transactions.new_opname'))
                ->modalHeading(__('transactions.modal_opname'))
                ->modalSubmitActionLabel(__('transactions.process_opname'))
                ->modalWidth('7xl')
                ->form([
                    FileUpload::make('file')
                        ->label(__('transactions.upload_excel'))
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, $livewire) {
                            if (! $state) return;
                            ini_set('memory_limit', '512M');
                            $import = new TransactionInPreviewImport();
                            Excel::import($import, $state->getRealPath());
                            $livewire->transactionRows = $import->rows;
                        }),

                    View::make('filament.transaction-in-modal-table')
                        ->viewData([
                            'inventoryMap' => Inventory::orderBy('barcode')
                                ->pluck('article', 'barcode')
                                ->toArray(),
                        ]),
                ])
                ->action(function () {
                    $sessionId = now()->timestamp;

                    foreach ($this->transactionRows as $row) {
                        if (empty($row['barcode'])) continue;

                        $status = $row['status'] ?? 'OK';

                        Transaction::create([
                            'session_id' => $sessionId,
                            'barcode'    => $row['barcode'],
                            'qty'        => (int) ($row['qty'] ?? 0),
                            'location'   => $row['location'] ?? null,
                            'bin'        => $row['bin'] ?? null,
                            'status'     => $status,
                            'type'       => 'OPNAME',
                            'remarks'    => $row['remarks'] ?? null,
                        ]);

                        if ($status === 'OK') {
                            Stock::updateOrCreate(
                                [
                                    'barcode'  => $row['barcode'],
                                    'location' => $row['location'] ?? null,
                                    'bin'      => $row['bin'] ?? null,
                                ],
                                ['qty' => (int) ($row['qty'] ?? 0)]
                            );
                        }
                    }

                    $this->transactionRows = [];

                    Notification::make()
                        ->title(__('transactions.opname_saved'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
