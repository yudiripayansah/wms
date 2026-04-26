<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::withSum('stocks', 'qty')->get();
    }

    public function headings(): array
    {
        return [
            'Kode Barang',
            'Brand',
            'Barcode',
            'SKU',
            'Nama Barang',
            'Colour',
            'Size',
            'Total Qty',
            'Price',
        ];
    }

    public function map($product): array
    {
        return [
            $product->kode_barang,
            $product->brand,
            $product->barcode,
            $product->sku,
            $product->nama_barang,
            $product->colour,
            $product->size,
            $product->stocks_sum_qty ?? 0,
            $product->price,
        ];
    }
}
