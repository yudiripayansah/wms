<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InventoriesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    use SkipsErrors, SkipsFailures;

    public int $success = 0;
    public int $updated = 0;
    public int $failed  = 0;

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows): void
    {
        $now = now()->toDateTimeString();

        $normalized = $rows->map(function ($row) {
            $r = array_change_key_case($row->toArray(), CASE_LOWER);
            return array_map(fn($v) => trim((string) $v), $r);
        })->filter(fn($r) => $r['barcode'] !== '');

        if ($normalized->isEmpty()) return;

        $barcodes = $normalized->pluck('barcode')->unique()->values();

        $existingBarcodes = DB::table('inventories')
            ->whereIn('barcode', $barcodes)
            ->pluck('barcode', 'barcode');

        $toInsert = [];
        $toUpdate = [];

        foreach ($normalized as $row) {
            $barcode = $row['barcode'];
            $payload = [
                'barcode'    => $barcode,
                'brand'      => $row['brand']   !== '' ? $row['brand']   : null,
                'sku'        => $row['sku']      !== '' ? $row['sku']     : null,
                'article'    => $row['article']  !== '' ? $row['article'] : null,
                'color'      => $row['color']    !== '' ? $row['color']   : null,
                'size'       => $row['size']     !== '' ? $row['size']    : null,
                'updated_at' => $now,
            ];

            if ($existingBarcodes->has($barcode)) {
                $toUpdate[] = $payload;
                $this->updated++;
            } else {
                $toInsert[] = array_merge($payload, ['created_at' => $now]);
                $this->success++;
            }
        }

        foreach (array_chunk($toInsert, 500) as $chunk) {
            try {
                $inserted = DB::table('inventories')->insertOrIgnore($chunk);
                // insertOrIgnore skips rows that violate unique constraint (within-chunk duplicates)
                $skipped = count($chunk) - $inserted;
                $this->failed   += $skipped;
                $this->success  -= $skipped;
            } catch (\Throwable) {
                $this->failed   += count($chunk);
                $this->success  -= count($chunk);
            }
        }

        foreach (array_chunk($toUpdate, 500) as $chunk) {
            try {
                DB::table('inventories')->upsert(
                    $chunk,
                    ['barcode'],
                    ['brand', 'sku', 'article', 'color', 'size', 'updated_at']
                );
            } catch (\Throwable) {
                $this->failed   += count($chunk);
                $this->updated  -= count($chunk);
            }
        }
    }
}
