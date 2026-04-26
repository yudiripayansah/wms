<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionExportController extends Controller
{
    private array $titles = [
        'IN'     => 'Barang Masuk',
        'OUT'    => 'Barang Keluar',
        'OPNAME' => 'Stock Opname',
    ];

    public function pdf(string $type)
    {
        ini_set('memory_limit', '512M');

        $type  = strtoupper($type);
        $title = $this->titles[$type] ?? 'Transaksi';

        // Hanya ambil kolom yang dibutuhkan agar hemat memory
        $transactions = Transaction::with(['product:kode_barang,nama_barang,colour,size'])
            ->where('type', $type)
            ->select(['id', 'session_id', 'kode_barang', 'qty', 'location', 'box', 'status', 'remarks', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        $pdf = Pdf::loadView('pdf.transactions', compact('transactions', 'title'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->download(strtolower(str_replace(' ', '-', $title)) . '.pdf');
    }
}
