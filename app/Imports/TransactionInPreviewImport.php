<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TransactionInPreviewImport implements ToCollection, WithHeadingRow
{
    public array $rows = [];

    public function collection(Collection $rows)
    {
        // Load all products in one query → no N+1
        $products = Product::pluck('nama_barang', 'kode_barang');

        foreach ($rows as $row) {
            $row = array_change_key_case($row->toArray(), CASE_LOWER);
            $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

            $kode = $row['kode_barang'] ?? '';
            if (empty($kode)) continue;

            $nama = $products->get($kode);

            $this->rows[] = [
                'kode_barang' => $kode,
                'nama_barang' => $nama ?? '',
                'qty'         => is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 1,
                'location'    => $row['location'] ?? null,
                'box'         => $row['box'] ?? null,
                'status'      => $nama ? 'OK' : 'DECLINED',
                'remarks'     => $nama ? '' : 'Produk tidak ditemukan',
            ];
        }
    }
}
