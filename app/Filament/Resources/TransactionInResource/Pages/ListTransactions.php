<?php

namespace App\Filament\Resources\TransactionInResource\Pages;

use App\Filament\Resources\TransactionInResource;
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
    protected static string $resource = TransactionInResource::class;

    public array $transactionRows = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newTransaction')
                ->label(__('transactions.new_in'))
                ->modalHeading(__('transactions.modal_in'))
                ->modalSubmitActionLabel(__('transactions.process_in'))
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
                            'type'       => 'IN',
                            'remarks'    => $row['remarks'] ?? null,
                        ]);

                        if ($status === 'OK') {
                            $stock = Stock::where('barcode', $row['barcode'])
                                ->where('location', $row['location'] ?? null)
                                ->where('bin', $row['bin'] ?? null)
                                ->first();

                            if ($stock) {
                                $stock->increment('qty', (int) ($row['qty'] ?? 0));
                            } else {
                                Stock::create([
                                    'barcode'  => $row['barcode'],
                                    'qty'      => (int) ($row['qty'] ?? 0),
                                    'location' => $row['location'] ?? null,
                                    'bin'      => $row['bin'] ?? null,
                                ]);
                            }
                        }
                    }

                    $this->transactionRows = [];

                    Notification::make()
                        ->title(__('transactions.saved'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
