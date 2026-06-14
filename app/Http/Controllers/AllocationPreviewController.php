<?php

namespace App\Http\Controllers;

use App\Models\Allocation;
use App\Models\Inventory;
use App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

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
        // COMPLETED: use actual OUT transactions (exact location/bin used).
        // Others: simulate greedy distribution across stock locations (same algorithm as complete action).
        $items = $allocation->status === 'COMPLETED'
            ? $this->buildFromTransactions($allocation)
            : $this->buildWithSimulatedLocations($allocation);

        if ($form === 'barcode') {
            return $items
                ->sortBy(fn($item) => $item->barcode)
                ->groupBy(fn($item) => $item->barcode);
        }

        return $items
            ->sortBy(fn($item) => $item->location ?? 'ZZZZ')
            ->groupBy(fn($item) => $item->location ?? '(No Location)');
    }

    /**
     * For non-COMPLETED allocations: simulate greedy deduction from highest-qty
     * stock locations to show a realistic location/bin breakdown per barcode.
     */
    private function buildWithSimulatedLocations(Allocation $allocation): \Illuminate\Support\Collection
    {
        $allocationItems = $allocation->items()->with('inventory')->get();
        $barcodes        = $allocationItems->pluck('barcode')->unique()->toArray();

        $stocksByBarcode = Stock::whereIn('barcode', $barcodes)
            ->orderByDesc('qty')
            ->get()
            ->groupBy('barcode')
            ->map(fn($g) => $g->values());

        $result = collect();

        foreach ($allocationItems as $item) {
            $remaining  = (int) $item->qty;
            $itemStocks = $stocksByBarcode->get($item->barcode, collect());

            if ($itemStocks->isEmpty() || $remaining <= 0) {
                $result->push($item);
                continue;
            }

            foreach ($itemStocks as $stock) {
                if ($remaining <= 0) break;

                $take = min($remaining, (int) $stock->qty);
                if ($take <= 0) continue;

                $remaining -= $take;

                $result->push((object) [
                    'barcode'   => $item->barcode,
                    'qty'       => $take,
                    'location'  => $stock->location,
                    'bin'       => $stock->bin,
                    'inventory' => $item->inventory,
                ]);
            }

            // Insufficient stock — show remaining as unlocated
            if ($remaining > 0) {
                $result->push((object) [
                    'barcode'   => $item->barcode,
                    'qty'       => $remaining,
                    'location'  => null,
                    'bin'       => null,
                    'inventory' => $item->inventory,
                ]);
            }
        }

        return $result;
    }

    /**
     * Build item collection from OUT transactions for a COMPLETED allocation.
     * Each transaction row becomes one item (accurate location/bin per deduction).
     */
    private function buildFromTransactions(Allocation $allocation): \Illuminate\Support\Collection
    {
        $txns = DB::table('transactions')
            ->where('type', 'OUT')
            ->where('remarks', 'Allocation: ' . $allocation->session_id)
            ->get();

        $barcodes    = $txns->pluck('barcode')->unique()->toArray();
        $inventories = Inventory::whereIn('barcode', $barcodes)
            ->get(['barcode', 'article', 'color', 'size'])
            ->keyBy('barcode');

        return $txns->map(function ($txn) use ($inventories) {
            $inv = $inventories->get($txn->barcode);

            // Return a plain object that mimics the AllocationItem + inventory structure
            return (object) [
                'barcode'   => $txn->barcode,
                'qty'       => $txn->qty,
                'location'  => $txn->location,
                'bin'       => $txn->bin,
                'inventory' => $inv ? (object) [
                    'article' => $inv->article,
                    'color'   => $inv->color,
                    'size'    => $inv->size,
                ] : null,
            ];
        });
    }
}
