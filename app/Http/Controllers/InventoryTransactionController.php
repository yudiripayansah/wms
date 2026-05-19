<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryTransactionController extends Controller
{
    public function pdf(string $barcode)
    {
        ini_set('memory_limit', '512M');

        $inventory = Inventory::where('barcode', $barcode)->firstOrFail();

        $transactions = Transaction::with(['inventory:barcode,article,color,size'])
            ->where('barcode', $barcode)
            ->select(['id', 'session_id', 'barcode', 'qty', 'type', 'location', 'bin', 'status', 'remarks', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        $title = __('transactions.history_title', ['barcode' => $inventory->barcode]) . ' / ' . $inventory->article;

        $pdf = Pdf::loadView('pdf.inventory-transactions', compact('transactions', 'title', 'inventory'))
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->download('histori-' . $barcode . '.pdf');
    }
}
