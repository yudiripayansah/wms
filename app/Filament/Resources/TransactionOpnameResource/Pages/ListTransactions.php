<?php

namespace App\Filament\Resources\TransactionOpnameResource\Pages;

use App\Concerns\HasTransactionRows;
use App\Filament\Resources\TransactionOpnameResource;
use App\Imports\TransactionBulkImport;
use App\Imports\TransactionInPreviewImport;
use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ListTransactions extends ListRecords
{
    use HasTransactionRows;

    protected static string $resource = TransactionOpnameResource::class;

    public array $transactionRows = [];
    public int   $totalImportRows = 0;

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
                            set_time_limit(0);
                            ini_set('memory_limit', '512M');

                            $import = new TransactionInPreviewImport();
                            Excel::import($import, $state->getRealPath());

                            $inventories = Inventory::whereIn('barcode', array_keys($import->accumulated))
                                ->get(['barcode', 'article', 'sku', 'color', 'size'])
                                ->keyBy('barcode');

                            $livewire->transactionRows = collect($import->accumulated)
                                ->map(function ($data) use ($inventories) {
                                    $inv = $inventories->get($data['barcode']);
                                    return [
                                        'barcode'  => $data['barcode'],
                                        'article'  => $inv?->article ?? '',
                                        'sku'      => $inv?->sku     ?? '',
                                        'color'    => $inv?->color   ?? '',
                                        'size'     => $inv?->size    ?? '',
                                        'qty'      => $data['qty'],
                                        'location' => $data['location'],
                                        'bin'      => $data['bin'],
                                        'status'   => $inv ? 'OK' : 'DECLINED',
                                        'remarks'  => $inv ? '' : 'Inventory tidak ditemukan',
                                    ];
                                })
                                ->values()
                                ->toArray();

                            $livewire->totalImportRows = $import->totalRawRows;
                        }),

                    View::make('filament.transaction-in-modal-table')
                        ->viewData([
                            'inventoryMap' => Inventory::orderBy('barcode')
                                ->get(['barcode', 'article', 'sku', 'color', 'size'])
                                ->keyBy('barcode')
                                ->map(fn($inv) => [
                                    'article' => $inv->article,
                                    'sku'     => $inv->sku,
                                    'color'   => $inv->color,
                                    'size'    => $inv->size,
                                ])
                                ->toArray(),
                        ]),
                ])
                ->action(function (array $data) {
                    set_time_limit(0);
                    ini_set('memory_limit', '512M');
                    $sessionId = (string) now()->timestamp;
                    $now       = now()->toDateTimeString();

                    [$count] = $this->commitRows($this->transactionRows, 'OPNAME', $sessionId, $now);

                    $this->transactionRows = [];
                    $this->totalImportRows = 0;

                    Notification::make()
                        ->title(__('transactions.opname_saved'))
                        ->body(__('transactions.items_recorded', ['count' => $count]))
                        ->success()
                        ->send();
                }),
        ];
    }

    private function commitRows(array $rows, string $type, string $sessionId, string $now): array
    {
        $txBatch  = [];
        $stockOps = [];

        foreach ($rows as $row) {
            if (empty($row['barcode'])) continue;

            $status   = $row['status']   ?? 'OK';
            $qty      = (int) ($row['qty']  ?? 0);
            $location = $row['location'] ?? null;
            $bin      = $row['bin']      ?? null;

            $txBatch[] = [
                'session_id' => $sessionId,
                'barcode'    => $row['barcode'],
                'qty'        => $qty,
                'location'   => $location,
                'bin'        => $bin,
                'status'     => $status,
                'type'       => $type,
                'remarks'    => $row['remarks'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($status === 'OK' && $qty > 0) {
                $key = "{$row['barcode']}|{$location}|{$bin}";
                // OPNAME: last value wins (absolute qty)
                $stockOps[$key] = ['barcode' => $row['barcode'], 'location' => $location, 'bin' => $bin, 'qty' => $qty];
            }
        }

        foreach (array_chunk($txBatch, 500) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }

        $this->applyOpnameStocks($stockOps, $now);

        return [count($txBatch)];
    }

    private function applyOpnameStocks(array $ops, string $now): void
    {
        if (empty($ops)) return;

        $barcodes = array_unique(array_column($ops, 'barcode'));
        $existing = Stock::whereIn('barcode', $barcodes)->get()
            ->keyBy(fn($s) => $s->barcode . '|' . ($s->location ?? '') . '|' . ($s->bin ?? ''));

        $toInsert = [];

        foreach ($ops as $key => $op) {
            $stock = $existing->get($key);
            if ($stock) {
                $stock->update(['qty' => $op['qty'], 'updated_at' => $now]);
            } else {
                $toInsert[] = [
                    'barcode'    => $op['barcode'],
                    'location'   => $op['location'],
                    'bin'        => $op['bin'],
                    'qty'        => $op['qty'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($toInsert, 500) as $chunk) {
            DB::table('stocks')->insert($chunk);
        }
    }
}
