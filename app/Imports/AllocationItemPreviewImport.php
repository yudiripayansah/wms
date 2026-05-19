<?php

namespace App\Imports;

use App\Models\Inventory;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AllocationItemPreviewImport implements ToCollection, WithHeadingRow
{
    public array $rows = [];

    public function collection(Collection $rows)
    {
        $inventories = Inventory::pluck('article', 'barcode');
        $stocks      = Stock::select('barcode', 'location', 'bin')
            ->orderByDesc('qty')
            ->get()
            ->unique('barcode')
            ->keyBy('barcode');

        foreach ($rows as $row) {
            $row     = array_change_key_case($row->toArray(), CASE_LOWER);
            $row     = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
            $barcode = $row['barcode'] ?? '';
            if (empty($barcode)) continue;

            $stock = $stocks->get($barcode);

            $this->rows[] = [
                'barcode'  => $barcode,
                'article'  => $inventories->get($barcode, ''),
                'qty'      => is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 0,
                'location' => !empty($row['location']) ? $row['location'] : ($stock?->location ?? ''),
                'bin'      => !empty($row['bin'])      ? $row['bin']      : ($stock?->bin      ?? ''),
            ];
        }
    }
}
