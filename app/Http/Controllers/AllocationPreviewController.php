<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use Barryvdh\DomPDF\Facade\Pdf;

class AllocationPreviewController extends Controller
{
    public function preview(Allocation $allocation, string $form = 'location')
    {
        $form    = in_array($form, ['location', 'barcode']) ? $form : 'location';
        $grouped = $this->buildGrouped($allocation, $form);

        return view('allocation.preview', [
            'allocation' => $allocation,
            'grouped'    => $grouped,
            'form'       => $form,
        ]);
    }

    public function pdf(Allocation $allocation, string $form = 'location')
    {
        ini_set('memory_limit', '512M');

        $form    = in_array($form, ['location', 'barcode']) ? $form : 'location';
        $grouped = $this->buildGrouped($allocation, $form);

        $filename = 'allocation-' . $allocation->session_id . '-by-' . $form . '.pdf';

        $pdf = Pdf::loadView('pdf.allocation', [
                'allocation' => $allocation,
                'grouped'    => $grouped,
                'form'       => $form,
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => false,
                'defaultFont'          => 'DejaVu Sans',
                'dpi'                  => 96,
                'isFontSubsettingEnabled' => true,
            ]);

        return $pdf->download($filename);
    }

    private function buildGrouped(Allocation $allocation, string $form): \Illuminate\Support\Collection
    {
        $items = $allocation->items()
            ->with('inventory')
            ->get();

        if ($form === 'barcode') {
            return $items
                ->sortBy(fn($item) => $item->barcode)
                ->groupBy(fn($item) => $item->barcode);
        }

        return $items
            ->sortBy(fn($item) => $item->location ?? 'ZZZZ')
            ->groupBy(fn($item) => $item->location ?? '(No Location)');
    }
}
