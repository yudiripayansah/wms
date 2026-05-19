<?php

namespace App\Imports;

use App\Models\Inventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransactionInPreviewImport implements ToCollection, WithHeadingRow
{
    public array $rows = [];

    public function collection(Collection $rows)
    {
        $inventories = Inventory::pluck('article', 'barcode');

        foreach ($rows as $row) {
            $row     = array_change_key_case($row->toArray(), CASE_LOWER);
            $row     = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
            $barcode = $row['barcode'] ?? '';
            if (empty($barcode)) continue;

            $article = $inventories->get($barcode);

            $this->rows[] = [
                'barcode'  => $barcode,
                'article'  => $article ?? '',
                'qty'      => is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 1,
                'location' => $row['location'] ?? null,
                'bin'      => $row['bin'] ?? null,
                'status'   => $article ? 'OK' : 'DECLINED',
                'remarks'  => $article ? '' : 'Inventory tidak ditemukan',
            ];
        }
    }
}
