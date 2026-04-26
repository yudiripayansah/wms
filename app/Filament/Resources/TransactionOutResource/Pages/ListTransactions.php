<?php

namespace App\Filament\Resources\TransactionOutResource\Pages;

use App\Filament\Resources\TransactionOutResource;
use App\Imports\TransactionInPreviewImport;
use App\Models\Allocation;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transaction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionOutResource::class;

    public array $transactionRows = [];

    protected function getActions(): array
    {
        return [
            Action::make('importFromAllocation')
                ->label('Import dari Allocation')
                ->modalHeading('Import dari Allocation')
                ->modalButton('Proses ke Transaction OUT')
                ->color('secondary')
                ->form([
                    Select::make('allocation_id')
                        ->label('Pilih Allocation')
                        ->options(
                            Allocation::where('status', 'CONFIRMED')
                                ->get()
                                ->mapWithKeys(fn($a) => [
                                    $a->id => 'Session ' . $a->session_id
                                        . ' — ' . $a->items()->count() . ' produk'
                                        . ($a->remarks ? ' | ' . $a->remarks : ''),
                                ])
                                ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->placeholder('Pilih allocation yang sudah dikonfirmasi...'),
                ])
                ->action(function (array $data) {
                    $allocation = Allocation::with('items')->findOrFail($data['allocation_id']);

                    if ($allocation->status !== 'CONFIRMED') {
                        Notification::make()
                            ->title('Allocation belum dikonfirmasi')
                            ->danger()
                            ->send();
                        return;
                    }

                    $sessionId = now()->timestamp;

                    foreach ($allocation->items as $item) {
                        Transaction::create([
                            'session_id'  => $sessionId,
                            'kode_barang' => $item->kode_barang,
                            'qty'         => $item->qty,
                            'location'    => $item->location,
                            'box'         => $item->box,
                            'type'        => 'OUT',
                            'status'      => 'OK',
                            'remarks'     => 'Allocation: ' . $allocation->session_id,
                        ]);

                        $stock = Stock::where('kode_barang', $item->kode_barang)
                            ->where('location', $item->location)
                            ->where('box', $item->box)
                            ->first();

                        if ($stock) {
                            $stock->decrement('qty', $item->qty);
                        }
                    }

                    $allocation->update(['status' => 'PROCESSED']);

                    Notification::make()
                        ->title('Allocation berhasil diproses ke Transaction OUT')
                        ->body($allocation->items->count() . ' produk berhasil dicatat.')
                        ->success()
                        ->send();
                }),

            Action::make('newTransaction')
                ->label('New Barang Keluar')
                ->modalHeading('Transaksi Barang Keluar')
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
                            'type'        => 'OUT',
                            'remarks'     => $row['remarks'] ?? null,
                        ]);

                        if ($status === 'OK' && $product) {
                            $stock = Stock::where('kode_barang', $row['kode_barang'])
                                ->where('location', $row['location'] ?? null)
                                ->where('box', $row['box'] ?? null)
                                ->first();

                            if ($stock) {
                                $stock->decrement('qty', (int) ($row['qty'] ?? 0));
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
