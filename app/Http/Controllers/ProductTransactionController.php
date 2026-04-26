<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductTransactionController extends Controller
{
    public function pdf(string $kodeBarang)
    {
        ini_set('memory_limit', '512M');

        $product = Product::where('kode_barang', $kodeBarang)->firstOrFail();

        $transactions = Transaction::with(['product:kode_barang,nama_barang,colour,size'])
            ->where('kode_barang', $kodeBarang)
            ->select(['id', 'session_id', 'kode_barang', 'qty', 'type', 'location', 'box', 'status', 'remarks', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        $title = 'Histori Transaksi - ' . $product->kode_barang . ' / ' . $product->nama_barang;

        $pdf = Pdf::loadView('pdf.product-transactions', compact('transactions', 'title', 'product'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->download('histori-' . $kodeBarang . '.pdf');
    }
}
