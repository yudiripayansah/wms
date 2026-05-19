<?php

namespace App\Imports;

use App\Models\AllocationItem;
use App\Models\Inventory;
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
        $inventories = Inventory::pluck('barcode', 'barcode');
        $stocks      = Stock::orderByDesc('qty')->get()->groupBy('barcode');

        foreach ($rows as $row) {
            $row = array_change_key_case($row->toArray(), CASE_LOWER);
            $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

            $barcode = $row['barcode'] ?? '';
            if (empty($barcode) || ! $inventories->has($barcode)) {
                $this->skipped++;
                continue;
            }

            $location = $row['location'] ?? null;
            $bin      = $row['bin'] ?? null;

            if (empty($location) && empty($bin)) {
                $stock    = $stocks->get($barcode)?->first();
                $location = $stock?->location;
                $bin      = $stock?->bin;
            }

            AllocationItem::create([
                'allocation_id' => $this->allocationId,
                'barcode'       => $barcode,
                'qty'           => is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 0,
                'location'      => $location,
                'bin'           => $bin,
            ]);

            $this->imported++;
        }
    }
}
