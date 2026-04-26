<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AllocationItemPreviewImport implements ToCollection, WithHeadingRow
{
    public array $rows = [];

    public function collection(Collection $rows)
    {
        $products = Product::pluck('nama_barang', 'kode_barang');
        $stocks   = Stock::select('kode_barang', 'location', 'box')
            ->orderByDesc('qty')
            ->get()
            ->unique('kode_barang')
            ->keyBy('kode_barang');

        foreach ($rows as $row) {
            $row  = array_change_key_case($row->toArray(), CASE_LOWER);
            $row  = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);
            $kode = $row['kode_barang'] ?? '';
            if (empty($kode)) continue;

            $stock = $stocks->get($kode);

            $this->rows[] = [
                'kode_barang' => $kode,
                'nama_barang' => $products->get($kode, ''),
                'qty'         => is_numeric($row['qty'] ?? null) ? (int) $row['qty'] : 0,
                'location'    => !empty($row['location']) ? $row['location'] : ($stock?->location ?? ''),
                'box'         => !empty($row['box'])      ? $row['box']      : ($stock?->box      ?? ''),
            ];
        }
    }
}
