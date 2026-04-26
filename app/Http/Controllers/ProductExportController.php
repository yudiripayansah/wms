<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Product;

class ProductExportController extends Controller
{
    public function pdf()
    {
        ini_set('memory_limit', '512M');

        $products = Product::withSum('stocks', 'qty')
            ->select(['id', 'kode_barang', 'nama_barang', 'brand', 'price'])
            ->get();

        $pdf = Pdf::loadView('pdf.products', compact('products'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'sans-serif',
            ]);

        return $pdf->download('products.pdf');
    }
}
