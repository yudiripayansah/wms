<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Bulk import for Transaction IN / OUT / OPNAME when file exceeds preview limit.
 * Processes in chunks of 1000 rows — never loads the full file into memory at once.
 */
class TransactionBulkImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public int $processed = 0;
    public int $skipped   = 0;

    public function __construct(
        private readonly string $type,
        private readonly string $sessionId
    ) {}

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows): void
    {
        $now      = now()->toDateTimeString();
        $barcodes = $rows->pluck('barcode')->filter()->map(fn($v) => trim((string) $v))->unique();

        $validBarcodes = Inventory::whereIn('barcode', $barcodes)->pluck('barcode', 'barcode');

        $txRecords = [];
        $stockOps  = []; // [key => [barcode, location, bin, qty]]

        foreach ($rows as $raw) {
            $row     = array_change_key_case($raw->toArray(), CASE_LOWER);
            $row     = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
            $barcode = $row['barcode'] ?? '';

            if (empty($barcode)) {
                $this->skipped++;
                continue;
            }

            $status   = $validBarcodes->has($barcode) ? 'OK' : 'DECLINED';
            $qty      = is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 0;
            $location = $row['location'] ?? null;
            $bin      = $row['bin']      ?? null;

            $txRecords[] = [
                'session_id' => $this->sessionId,
                'barcode'    => $barcode,
                'qty'        => $qty,
                'location'   => $location,
                'bin'        => $bin,
                'status'     => $status,
                'type'       => $this->type,
                'remarks'    => $row['remarks'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($status === 'OK' && $qty > 0) {
                $key = "{$barcode}|{$location}|{$bin}";
                if (! isset($stockOps[$key])) {
                    $stockOps[$key] = [
                        'barcode'  => $barcode,
                        'location' => $location,
                        'bin'      => $bin,
                        'qty'      => 0,
                    ];
                }
                // OPNAME: last value wins (absolute qty); IN/OUT: sum deltas
                if ($this->type === 'OPNAME') {
                    $stockOps[$key]['qty'] = $qty;
                } else {
                    $stockOps[$key]['qty'] += $qty;
                }
            }

            $this->processed++;
        }

        // Bulk insert transactions (one statement per 500 rows)
        foreach (array_chunk($txRecords, 500) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }

        $this->applyStocks($stockOps, $now);
    }

    private function applyStocks(array $ops, string $now): void
    {
        if (empty($ops)) return;

        // Single query to fetch all affected stocks for this chunk
        $barcodes = array_unique(array_column($ops, 'barcode'));
        $existing = Stock::whereIn('barcode', $barcodes)->get()
            ->keyBy(fn($s) => $s->barcode . '|' . ($s->location ?? '') . '|' . ($s->bin ?? ''));

        $toInsert = [];

        foreach ($ops as $key => $op) {
            $stock = $existing->get($key);

            if ($stock) {
                match ($this->type) {
                    'IN'     => $stock->increment('qty', $op['qty']),
                    'OUT'    => $stock->decrement('qty', min($op['qty'], $stock->qty)),
                    'OPNAME' => $stock->update(['qty' => $op['qty'], 'updated_at' => $now]),
                    default  => null,
                };
            } elseif ($this->type !== 'OUT') {
                // Only create new stock for IN and OPNAME
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
