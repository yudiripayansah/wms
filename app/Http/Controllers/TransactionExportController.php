<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionExportController extends Controller
{
    private function titleForType(string $type): string
    {
        return match ($type) {
            'IN'     => __('transactions.stock_in'),
            'OUT'    => __('transactions.stock_out'),
            'OPNAME' => __('transactions.opname'),
            default  => __('navigation.transactions'),
        };
    }

    public function pdf(string $type)
    {
        ini_set('memory_limit', '512M');

        $type  = strtoupper($type);
        $title = $this->titleForType($type);

        $transactions = Transaction::with(['inventory:barcode,article,color,size'])
            ->where('type', $type)
            ->select(['id', 'session_id', 'barcode', 'qty', 'location', 'bin', 'status', 'remarks', 'created_at'])
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
