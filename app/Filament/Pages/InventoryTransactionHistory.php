<?php

namespace App\Filament\Pages;

use App\Exports\InventoryTransactionExport;
use App\Models\Inventory;
use App\Models\Transaction;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class InventoryTransactionHistory extends Page
{
    protected static string $view = 'filament.pages.inventory-transaction-history';

    protected static bool $shouldRegisterNavigation = false;

    public string $barcode = '';

    protected $queryString = ['barcode'];

    public function getTitle(): string
    {
        return __('transactions.history_title', ['barcode' => $this->barcode]);
    }

    public function getInventory(): ?Inventory
    {
        return Inventory::where('barcode', $this->barcode)->first();
    }

    public function getTransactions()
    {
        return Transaction::with('inventory')
            ->where('barcode', $this->barcode)
            ->orderByDesc('created_at')
            ->get();
    }

    public function exportExcel()
    {
        return Excel::download(
            new InventoryTransactionExport($this->barcode),
            'histori-' . $this->barcode . '.xlsx'
        );
    }

    public function getPdfUrl(): string
    {
        return url('/export-inventory-transactions-pdf/' . urlencode($this->barcode));
    }
}
