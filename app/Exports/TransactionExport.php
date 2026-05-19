<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected string $type) {}

    public function collection()
    {
        return Transaction::with('inventory')
            ->where('type', $this->type)
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
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
