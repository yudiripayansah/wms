<?php

namespace App\Filament\Pages;

use App\Exports\ProductTransactionExport;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class ProductTransactionHistory extends Page
{
    protected static string $view = 'filament.pages.product-transaction-history';

    protected static bool $shouldRegisterNavigation = false;

    public string $kode_barang = '';

    protected $queryString = ['kode_barang'];

    public function getTitle(): string
    {
        return 'Histori Transaksi: ' . $this->kode_barang;
    }

    public function getProduct(): ?Product
    {
        return Product::where('kode_barang', $this->kode_barang)->first();
    }

    public function getTransactions()
    {
        return Transaction::with('product')
            ->where('kode_barang', $this->kode_barang)
            ->orderByDesc('created_at')
            ->get();
    }

    public function exportExcel()
    {
        return Excel::download(
            new ProductTransactionExport($this->kode_barang),
            'histori-' . $this->kode_barang . '.xlsx'
        );
    }

    public function getPdfUrl(): string
    {
        return url('/export-product-transactions-pdf/' . urlencode($this->kode_barang));
    }
}
