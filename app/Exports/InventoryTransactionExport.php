<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryTransactionExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected string $barcode) {}

    public function collection()
    {
        return Transaction::with('inventory')
            ->where('barcode', $this->barcode)
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tipe',
            'Session ID',
            'Barcode',
            'Article',
            'Color',
            'Size',
            'Qty',
            'Location',
            'Bin',
            'Status',
            'Keterangan',
            'Tanggal',
        ];
    }

    public function map($row): array
    {
        return [
            $row->type,
            $row->session_id,
            $row->barcode,
            $row->inventory?->article,
            $row->inventory?->color,
            $row->inventory?->size,
            $row->qty,
            $row->location,
            $row->bin,
            $row->status,
            $row->remarks,
            $row->created_at?->format('d/m/Y H:i'),
        ];
    }
}
