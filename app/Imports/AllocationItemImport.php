<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AllocationItemImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public int $imported = 0;
    public int $skipped  = 0;

    public function __construct(private readonly int $allocationId) {}

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows): void
    {
        $now      = now()->toDateTimeString();
        $barcodes = $rows->pluck('barcode')->filter()->map(fn($v) => trim((string) $v))->unique();

        // Single query per chunk for barcode validation
        $validBarcodes = Inventory::whereIn('barcode', $barcodes)
            ->pluck('barcode', 'barcode');

        // Single query per chunk for stock defaults
        $stockDefaults = Stock::whereIn('barcode', $barcodes)
            ->orderByDesc('qty')
            ->get()
            ->groupBy('barcode')
            ->map(fn($g) => $g->first());

        $toInsert = [];

        foreach ($rows as $raw) {
            $row     = array_change_key_case($raw->toArray(), CASE_LOWER);
            $row     = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
            $barcode = $row['barcode'] ?? '';

            if (empty($barcode) || ! $validBarcodes->has($barcode)) {
                $this->skipped++;
                continue;
            }

            $location = $row['location'] ?? null;
            $bin      = $row['bin']      ?? null;

            if (empty($location) && empty($bin)) {
                $stock    = $stockDefaults->get($barcode);
                $location = $stock?->location;
                $bin      = $stock?->bin;
            }

            $toInsert[] = [
                'allocation_id' => $this->allocationId,
                'barcode'       => $barcode,
                'qty'           => is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 0,
                'location'      => $location,
                'bin'           => $bin,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
            $this->imported++;
        }

        // Batch insert — one statement per 500 rows
        foreach (array_chunk($toInsert, 500) as $chunk) {
            DB::table('allocation_items')->insert($chunk);
        }
    }
}
