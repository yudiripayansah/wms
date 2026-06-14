<?php

namespace App\Filament\Resources\TransactionOutResource\Pages;

use App\Concerns\HasTransactionRows;
use App\Filament\Resources\TransactionOutResource;
use App\Imports\TransactionBulkImport;
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
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ListTransactions extends ListRecords
{
    use HasTransactionRows;

    protected static string $resource = TransactionOutResource::class;

    public array $transactionRows = [];
    public int   $totalImportRows = 0;

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

                    $now       = now()->toDateTimeString();
                    $sessionId = (string) now()->timestamp;
                    $txRecords = [];
                    $shortages = [];

                    try {
                        DB::transaction(function () use ($allocation, $sessionId, $now, &$txRecords, &$shortages) {
                            $barcodes = $allocation->items->pluck('barcode')->unique()->toArray();

                            // Lock stock rows — prevents concurrent allocations from double-spending stock
                            $stocksByBarcode = Stock::whereIn('barcode', $barcodes)
                                ->lockForUpdate()
                                ->orderByDesc('qty')
                                ->get()
                                ->groupBy('barcode')
                                ->map(fn($g) => $g->values());

                            // Validate ALL items first before touching anything
                            foreach ($allocation->items as $item) {
                                $available = ($stocksByBarcode->get($item->barcode) ?? collect())->sum('qty');
                                if ($available < $item->qty) {
                                    $shortages[] = "Barcode {$item->barcode}: tersedia {$available}, dibutuhkan {$item->qty}";
                                }
                            }

                            if (! empty($shortages)) {
                                throw new \RuntimeException('insufficient_stock');
                            }

                            // Deduct stock greedily from highest-qty location first
                            foreach ($allocation->items as $item) {
                                $remaining  = (int) $item->qty;
                                $itemStocks = $stocksByBarcode->get($item->barcode, collect());

                                foreach ($itemStocks as $stock) {
                                    if ($remaining <= 0) break;
                                    $deduct = min($remaining, (int) $stock->qty);
                                    if ($deduct <= 0) continue;

                                    $stock->decrement('qty', $deduct);
                                    $remaining -= $deduct;

                                    $txRecords[] = [
                                        'session_id' => $sessionId,
                                        'barcode'    => $item->barcode,
                                        'qty'        => $deduct,
                                        'location'   => $stock->location,
                                        'bin'        => $stock->bin,
                                        'type'       => 'OUT',
                                        'status'     => 'OK',
                                        'remarks'    => 'Allocation: ' . $allocation->session_id,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ];
                                }
                            }

                            foreach (array_chunk($txRecords, 500) as $chunk) {
                                DB::table('transactions')->insert($chunk);
                            }

                            $allocation->update(['status' => 'COMPLETED']);
                        });

                    } catch (\RuntimeException $e) {
                        if ($e->getMessage() === 'insufficient_stock') {
                            Notification::make()
                                ->title('Stok tidak mencukupi')
                                ->body(implode("\n", $shortages))
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }
                        throw $e;
                    }

                    Notification::make()
                        ->title(__('transactions.alloc_processed'))
                        ->body(__('transactions.items_recorded', ['count' => count($txRecords)]))
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

                    [$count] = $this->commitRows($this->transactionRows, 'OUT', $sessionId, $now);

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

        if ($stockOps) {
            $barcodes = array_unique(array_column($stockOps, 'barcode'));
            $existing = Stock::whereIn('barcode', $barcodes)->get()
                ->keyBy(fn($s) => $s->barcode . '|' . ($s->location ?? '') . '|' . ($s->bin ?? ''));

            foreach ($stockOps as $key => $op) {
                $stock = $existing->get($key);
                if ($stock) {
                    $stock->decrement('qty', min($op['qty'], $stock->qty));
                }
            }
        }

        return [count($txBatch)];
    }
}
