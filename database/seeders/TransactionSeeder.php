<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $locations = ['A1', 'A2', 'A3', 'A4', 'B1', 'B2', 'B3', 'C1', 'C2', 'D1', 'D2', 'E1', 'E2'];

        $allBarcodes = Inventory::pluck('barcode')->toArray();
        shuffle($allBarcodes);

        // --- 200 Transaction IN ---
        $inBarcodes     = array_slice($allBarcodes, 0, 200);
        $inTransactions = [];
        $stockMap       = [];

        foreach ($inBarcodes as $index => $barcode) {
            $qty       = rand(10, 50);
            $location  = $locations[array_rand($locations)];
            $bin       = 'BIN-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $sessionId = 'SESS-IN-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $inTransactions[] = [
                'session_id' => $sessionId,
                'barcode'    => $barcode,
                'qty'        => $qty,
                'location'   => $location,
                'bin'        => $bin,
                'status'     => 'OK',
                'type'       => 'IN',
                'remarks'    => 'Sample inbound — initial stock loading',
                'created_at' => now()->subDays(rand(30, 90))->subHours(rand(0, 23)),
                'updated_at' => now()->subDays(rand(30, 90)),
            ];

            $stockKey = "{$barcode}|{$location}";
            if (! isset($stockMap[$stockKey])) {
                $stockMap[$stockKey] = ['barcode' => $barcode, 'location' => $location, 'bin' => $bin, 'qty' => 0];
            }
            $stockMap[$stockKey]['qty'] += $qty;
        }

        foreach (array_chunk($inTransactions, 100) as $chunk) {
            Transaction::insert($chunk);
        }

        $now          = now();
        $stockRecords = [];
        foreach ($stockMap as $stock) {
            $existing = Stock::where('barcode', $stock['barcode'])
                ->where('location', $stock['location'])
                ->first();

            if ($existing) {
                $existing->increment('qty', $stock['qty']);
            } else {
                $stockRecords[] = [
                    'barcode'    => $stock['barcode'],
                    'qty'        => $stock['qty'],
                    'location'   => $stock['location'],
                    'bin'        => $stock['bin'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($stockRecords, 100) as $chunk) {
            Stock::insert($chunk);
        }

        $this->command->info('Seeded 200 IN transactions and updated stocks.');

        // --- 200 Transaction OUT ---
        $stockedBarcodes = Stock::where('qty', '>=', 5)->pluck('barcode')->toArray();
        shuffle($stockedBarcodes);
        $outBarcodes = array_slice($stockedBarcodes, 0, min(200, count($stockedBarcodes)));

        $outTransactions = [];

        foreach ($outBarcodes as $index => $barcode) {
            $stock = Stock::where('barcode', $barcode)->where('qty', '>', 0)->first();
            if (! $stock) continue;

            $maxOut    = min(10, $stock->qty);
            $qty       = rand(1, $maxOut);
            $sessionId = 'SESS-OUT-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $outTransactions[] = [
                'session_id' => $sessionId,
                'barcode'    => $barcode,
                'qty'        => $qty,
                'location'   => $stock->location,
                'bin'        => $stock->bin,
                'status'     => 'OK',
                'type'       => 'OUT',
                'remarks'    => 'Sample outbound — sales fulfilment',
                'created_at' => now()->subDays(rand(1, 29))->subHours(rand(0, 23)),
                'updated_at' => now()->subDays(rand(1, 29)),
            ];

            $stock->decrement('qty', $qty);
        }

        foreach (array_chunk($outTransactions, 100) as $chunk) {
            Transaction::insert($chunk);
        }

        $this->command->info('Seeded ' . count($outTransactions) . ' OUT transactions and decremented stocks.');

        // --- 50 Transaction OPNAME ---
        $allStockedBarcodes = Stock::where('qty', '>', 0)->pluck('barcode')->toArray();
        shuffle($allStockedBarcodes);
        $opnameBarcodes  = array_slice($allStockedBarcodes, 0, min(50, count($allStockedBarcodes)));
        $opnameSessionId = 'SESS-OPNAME-' . now()->format('Ymd');

        $opnameTransactions = [];

        foreach ($opnameBarcodes as $index => $barcode) {
            $stock = Stock::where('barcode', $barcode)->first();
            if (! $stock) continue;

            // Counted qty may differ slightly from system qty (simulates real opname)
            $variance   = rand(-2, 3);
            $countedQty = max(0, $stock->qty + $variance);

            $opnameTransactions[] = [
                'session_id' => $opnameSessionId . '-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'barcode'    => $barcode,
                'qty'        => $countedQty,
                'location'   => $stock->location,
                'bin'        => $stock->bin,
                'status'     => 'OK',
                'type'       => 'OPNAME',
                'remarks'    => 'Monthly stock count — ' . now()->format('M Y'),
                'created_at' => now()->subDays(rand(0, 7))->subHours(rand(0, 8)),
                'updated_at' => now()->subDays(rand(0, 7)),
            ];
        }

        foreach (array_chunk($opnameTransactions, 100) as $chunk) {
            Transaction::insert($chunk);
        }

        $this->command->info('Seeded ' . count($opnameTransactions) . ' OPNAME transactions.');
    }
}
