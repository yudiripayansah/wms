<?php

namespace App\Filament\Resources\TransactionInResource\Pages;

use App\Concerns\HasTransactionRows;
use App\Filament\Resources\TransactionInResource;
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

    protected static string $resource = TransactionInResource::class;

    public array $transactionRows  = [];
    public int   $totalImportRows  = 0;

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

                    [$count] = $this->commitRows($this->transactionRows, 'IN', $sessionId, $now);

                    $this->transactionRows = [];
                    $this->totalImportRows = 0;

                    Notification::make()
                        ->title(__('transactions.saved'))
                        ->body(__('transactions.items_recorded', ['count' => $count]))
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Bulk-insert transaction rows and apply stock changes efficiently.
     * Returns [processed_count].
     */
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
                if (! isset($stockOps[$key])) {
                    $stockOps[$key] = ['barcode' => $row['barcode'], 'location' => $location, 'bin' => $bin, 'qty' => 0];
                }
                $stockOps[$key]['qty'] += $qty;
            }
        }

        foreach (array_chunk($txBatch, 500) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }

        $this->applyStocks($stockOps, $type, $now);

        return [count($txBatch)];
    }

    private function applyStocks(array $ops, string $type, string $now): void
    {
        if (empty($ops)) return;

        $barcodes = array_unique(array_column($ops, 'barcode'));
        $existing = Stock::whereIn('barcode', $barcodes)->get()
            ->keyBy(fn($s) => $s->barcode . '|' . ($s->location ?? '') . '|' . ($s->bin ?? ''));

        $toInsert = [];

        foreach ($ops as $key => $op) {
            $stock = $existing->get($key);

            if ($stock) {
                match ($type) {
                    'IN'     => $stock->increment('qty', $op['qty']),
                    'OUT'    => $stock->decrement('qty', min($op['qty'], $stock->qty)),
                    'OPNAME' => $stock->update(['qty' => $op['qty'], 'updated_at' => $now]),
                    default  => null,
                };
            } elseif ($type !== 'OUT') {
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
