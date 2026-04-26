<?php

namespace App\Imports;

use App\Models\AllocationItem;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AllocationItemImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped  = 0;

    public function __construct(private int $allocationId) {}

    public function collection(Collection $rows)
    {
        $products = Product::pluck('kode_barang', 'kode_barang');
        $stocks   = Stock::orderByDesc('qty')->get()->groupBy('kode_barang');

        foreach ($rows as $row) {
            $row = array_change_key_case($row->toArray(), CASE_LOWER);
            $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

            $kode = $row['kode_barang'] ?? '';
            if (empty($kode) || ! $products->has($kode)) {
                $this->skipped++;
                continue;
            }

            $location = $row['location'] ?? null;
            $box      = $row['box'] ?? null;

            if (empty($location) && empty($box)) {
                $stock    = $stocks->get($kode)?->first();
                $location = $stock?->location;
                $box      = $stock?->box;
            }

            AllocationItem::create([
                'allocation_id' => $this->allocationId,
                'kode_barang'   => $kode,
                'qty'           => is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 0,
                'location'      => $location,
                'box'           => $box,
            ]);

            $this->imported++;
        }
    }
}
