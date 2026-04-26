<?php

namespace App\Filament\Resources\TransactionInResource\Pages;

use App\Filament\Resources\TransactionInResource;
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
    protected static string $resource = TransactionInResource::class;

    // Public property — entangled by Alpine, sent deferred on submit
    public array $transactionRows = [];

    protected function getActions(): array
    {
        return [
            Action::make('newTransaction')
                ->label('New Barang Masuk')
                ->modalHeading('Transaksi Barang Masuk')
                ->modalButton('Proses Transaksi')
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
                            'type'        => 'IN',
                            'remarks'     => $row['remarks'] ?? null,
                        ]);

                        if ($status === 'OK' && $product) {
                            $stock = Stock::where('kode_barang', $row['kode_barang'])
                                ->where('location', $row['location'] ?? null)
                                ->where('box', $row['box'] ?? null)
                                ->first();

                            if ($stock) {
                                $stock->increment('qty', (int) ($row['qty'] ?? 0));
                            } else {
                                Stock::create([
                                    'kode_barang' => $row['kode_barang'],
                                    'qty'         => (int) ($row['qty'] ?? 0),
                                    'location'    => $row['location'] ?? null,
                                    'box'         => $row['box'] ?? null,
                                ]);
                            }
                        }
                    }

                    $this->transactionRows = [];

                    Notification::make()
                        ->title('Transaksi berhasil disimpan')
                        ->success()
                        ->send();
                }),
        ];
    }
}
