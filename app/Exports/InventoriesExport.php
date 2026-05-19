<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoriesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Inventory::withSum('stocks', 'qty')->get();
    }

    public function headings(): array
    {
        return [
            'Barcode',
            'Brand',
            'SKU',
            'Article',
            'Color',
            'Size',
            'Total Qty',
        ];
    }

    public function map($inventory): array
    {
        return [
            $inventory->barcode,
            $inventory->brand,
            $inventory->sku,
            $inventory->article,
            $inventory->color,
            $inventory->size,
            $inventory->stocks_sum_qty ?? 0,
        ];
    }
}
