<?php

namespace App\Imports;

use App\Models\Inventory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class InventoriesImport implements ToCollection, WithHeadingRow
{
    use SkipsErrors, SkipsFailures;

    public $success = 0;
    public $updated = 0;
    public $failed  = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $row = array_change_key_case($row->toArray(), CASE_LOWER);
            $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

            try {
                if (empty($row['barcode'])) {
                    $this->failed++;
                    continue;
                }

                $data = [
                    'brand'   => $row['brand']   ?? null,
                    'sku'     => $row['sku']      ?? null,
                    'article' => $row['article']  ?? null,
                    'color'   => $row['color']    ?? null,
                    'size'    => $row['size']      ?? null,
                ];

                $inventory = Inventory::where('barcode', $row['barcode'])->first();

                if ($inventory) {
                    $inventory->update($data);
                    $this->updated++;
                } else {
                    Inventory::create(array_merge(['barcode' => $row['barcode']], $data));
                    $this->success++;
                }
            } catch (\Throwable $e) {
                $this->failed++;
            }
        }
    }
}
