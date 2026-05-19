<?php

namespace App\Filament\Resources\TransactionOutResource\Pages;

use App\Filament\Resources\TransactionOutResource;
use App\Imports\TransactionInPreviewImport;
use App\Models\Allocation;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionOutResource::class;

    public array $transactionRows = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importFromAllocation')
                ->label(__('transactions.import_from_alloc'))
                ->modalHeading(__('transactions.import_alloc_head'))
                ->modalSubmitActionLabel(__('transactions.process_to_out'))
                ->color('gray')
                ->form([
                    Select::make('allocation_id')
                        ->label(__('transactions.select_alloc'))
                        ->options(
                            Allocation::where('status', 'FINISHED')
                                ->get()
                                ->mapWithKeys(fn($a) => [
                                    $a->id => 'Session ' . $a->session_id
                                        . ' — ' . $a->items()->count() . ' ' . __('general.items')
                                        . ($a->remarks ? ' | ' . $a->remarks : ''),
                                ])
                                ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->placeholder(__('transactions.alloc_placeholder')),
                ])
                ->action(function (array $data) {
                    $allocation = Allocation::with('items')->findOrFail($data['allocation_id']);

                    if ($allocation->status !== 'FINISHED') {
                        Notification::make()->title(__('transactions.not_confirmed'))->danger()->send();
                        return;
                    }

                    $sessionId = now()->timestamp;

                    foreach ($allocation->items as $item) {
                        Transaction::create([
                            'session_id' => $sessionId,
                            'barcode'    => $item->barcode,
                            'qty'        => $item->qty,
                            'location'   => $item->location,
                            'bin'        => $item->bin,
                            'type'       => 'OUT',
                            'status'     => 'OK',
                            'remarks'    => 'Allocation: ' . $allocation->session_id,
                        ]);

                        $stock = Stock::where('barcode', $item->barcode)
                            ->where('location', $item->location)
                            ->where('bin', $item->bin)
                            ->first();

                        if ($stock) {
                            $stock->decrement('qty', $item->qty);
                        }
                    }

                    $allocation->update(['status' => 'COMPLETED']);

                    Notification::make()
                        ->title(__('transactions.alloc_processed'))
                        ->body(__('transactions.items_recorded', ['count' => $allocation->items->count()]))
                        ->success()
                        ->send();
                }),

            Action::make('newTransaction')
                ->label(__('transactions.new_out'))
                ->modalHeading(__('transactions.modal_out'))
                ->modalSubmitActionLabel(__('transactions.process_out'))
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
                            'type'       => 'OUT',
                            'remarks'    => $row['remarks'] ?? null,
                        ]);

                        if ($status === 'OK') {
                            $stock = Stock::where('barcode', $row['barcode'])
                                ->where('location', $row['location'] ?? null)
                                ->where('bin', $row['bin'] ?? null)
                                ->first();

                            if ($stock) {
                                $stock->decrement('qty', (int) ($row['qty'] ?? 0));
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
