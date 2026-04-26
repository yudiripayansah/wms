<?php

namespace App\Filament\Resources\TransactionOpnameResource\Pages;

use App\Filament\Resources\TransactionOpnameResource;
use App\Imports\TransactionInPreviewImport;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transaction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionOpnameResource::class;

    public array $transactionRows = [];

    protected function getActions(): array
    {
        return [
            Action::make('newTransaction')
                ->label('New Stock Opname')
                ->modalHeading('Stock Opname')
                ->modalButton('Proses Opname')
                ->modalWidth('7xl')
                ->form([
                    FileUpload::make('file')
                        ->label('Upload Excel (opsional — kolom: kode_barang, qty, location, box)')
                        ->disk('public')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, $livewire) {
                            if (!$state) return;
                            ini_set('memory_limit', '512M');
                            $import = new TransactionInPreviewImport();
                            Excel::import($import, $state->getRealPath());
                            $livewire->transactionRows = $import->rows;
                        }),

                    View::make('filament.transaction-in-modal-table')
                        ->viewData([
                            'productMap' => Product::orderBy('kode_barang')
                                ->pluck('nama_barang', 'kode_barang')
                                ->toArray(),
                        ]),
                ])
                ->action(function () {
                    $sessionId = now()->timestamp;

                    foreach ($this->transactionRows as $row) {
                        if (empty($row['kode_barang'])) continue;

                        $product = Product::where('kode_barang', $row['kode_barang'])->first();
                        $status  = $row['status'] ?? 'OK';

                        Transaction::create([
                            'session_id'  => $sessionId,
                            'kode_barang' => $row['kode_barang'],
                            'qty'         => (int) ($row['qty'] ?? 0),
                            'location'    => $row['location'] ?? null,
                            'box'         => $row['box'] ?? null,
                            'status'      => $status,
                            'type'        => 'OPNAME',
                            'remarks'     => $row['remarks'] ?? null,
                        ]);

                        // OPNAME: set stok ke nilai persis (bukan increment/decrement)
                        if ($status === 'OK' && $product) {
                            Stock::updateOrCreate(
                                [
                                    'kode_barang' => $row['kode_barang'],
                                    'location'    => $row['location'] ?? null,
                                    'box'         => $row['box'] ?? null,
                                ],
                                ['qty' => (int) ($row['qty'] ?? 0)]
                            );
                        }
                    }

                    $this->transactionRows = [];

                    Notification::make()
                        ->title('Stock Opname berhasil disimpan')
                        ->success()
                        ->send();
                }),
        ];
    }
}
