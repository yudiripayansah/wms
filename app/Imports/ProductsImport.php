<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ProductsImport implements ToCollection, WithHeadingRow
{
    use SkipsErrors, SkipsFailures;

    public $success = 0;
    public $updated = 0;
    public $failed = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            $row = array_change_key_case($row->toArray(), CASE_LOWER);
            $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

            try {
                if (empty($row['kode_barang']) || empty($row['nama_barang'])) {
                    $this->failed++;
                    continue;
                }

                $price = isset($row['price'])
                    ? floatval(str_replace(',', '.', str_replace('.', '', $row['price'])))
                    : 0;

                $product = Product::where('kode_barang', $row['kode_barang'])->first();

                $data = [
                    'brand'      => $row['brand'] ?? null,
                    'barcode'    => $row['barcode'] ?? null,
                    'sku'        => $row['sku'] ?? null,
                    'nama_barang' => $row['nama_barang'],
                    'colour'     => $row['colour'] ?? null,
                    'size'       => $row['size'] ?? null,
                    'price'      => $price,
                ];

                if ($product) {
                    $product->update($data);
                    $this->updated++;
                } else {
                    Product::create(array_merge(['kode_barang' => $row['kode_barang']], $data));
                    $this->success++;
                }
            } catch (\Throwable $e) {
                $this->failed++;
            }
        }
    }
}
