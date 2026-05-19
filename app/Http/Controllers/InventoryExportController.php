<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryExportController extends Controller
{
    public function pdf()
    {
        ini_set('memory_limit', '512M');

        $inventories = Inventory::withSum('stocks', 'qty')
            ->select(['id', 'barcode', 'brand', 'sku', 'article', 'color', 'size'])
            ->get();

        $pdf = Pdf::loadView('pdf.inventories', compact('inventories'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->download('inventories.pdf');
    }
}
