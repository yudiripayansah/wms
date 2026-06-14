<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads an Excel file and accumulates qty per unique barcode.
 * Inventory lookup (article, sku, color, size) is done externally after import
 * so we only hit the DB once for all barcodes.
 */
class TransactionInPreviewImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /** barcode → {barcode, qty, location, bin} */
    public array $accumulated  = [];
    public int   $totalRawRows = 0;

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $raw) {
            $row     = array_change_key_case($raw->toArray(), CASE_LOWER);
            $barcode = trim((string) ($row['barcode'] ?? ''));
            if ($barcode === '') continue;

            $this->totalRawRows++;
            $qty = is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 1;

            if (! isset($this->accumulated[$barcode])) {
                $this->accumulated[$barcode] = [
                    'barcode'  => $barcode,
                    'qty'      => 0,
                    'location' => $row['location'] ?? null,
                    'bin'      => $row['bin']      ?? null,
                ];
            }

            $this->accumulated[$barcode]['qty'] += $qty;
        }
    }
}
