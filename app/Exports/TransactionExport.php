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
        return Transaction::with('product')
            ->where('type', $this->type)
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Session ID',
            'Kode Barang',
            'Nama Barang',
            'Colour',
            'Size',
            'Qty',
            'Location',
            'Box',
            'Status',
            'Keterangan',
            'Tanggal',
        ];
    }

    public function map($row): array
    {
        return [
            $row->session_id,
            $row->kode_barang,
            $row->product?->nama_barang,
            $row->product?->colour,
            $row->product?->size,
            $row->qty,
            $row->location,
            $row->box,
            $row->status,
            $row->remarks,
            $row->created_at?->format('d/m/Y H:i'),
        ];
    }
}
