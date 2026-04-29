<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $locations = ['A1', 'A2', 'A3', 'A4', 'B1', 'B2', 'B3', 'C1', 'C2', 'D1', 'D2', 'E1', 'E2'];

        $allKodes = Product::pluck('kode_barang')->toArray();
        shuffle($allKodes);

        // --- 200 Transaction IN ---
        $inKodes = array_slice($allKodes, 0, 200);
        $inTransactions = [];
        $stockMap = [];

        foreach ($inKodes as $index => $kodeBarang) {
            $qty      = rand(10, 50);
            $location = $locations[array_rand($locations)];
            $box      = 'BOX-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $sessionId = 'SESS-IN-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $inTransactions[] = [
                'session_id'  => $sessionId,
                'kode_barang' => $kodeBarang,
                'qty'         => $qty,
                'location'    => $location,
                'box'         => $box,
                'status'      => 'OK',
                'type'        => 'IN',
                'remarks'     => 'Sample inbound - initial stock loading',
                'created_at'  => now()->subDays(rand(30, 90))->subHours(rand(0, 23)),
                'updated_at'  => now()->subDays(rand(30, 90)),
            ];

            // Track stock to create/update later
            $stockKey = "{$kodeBarang}|{$location}";
            if (!isset($stockMap[$stockKey])) {
                $stockMap[$stockKey] = ['kode_barang' => $kodeBarang, 'location' => $location, 'box' => $box, 'qty' => 0];
            }
            $stockMap[$stockKey]['qty'] += $qty;
        }

        foreach (array_chunk($inTransactions, 100) as $chunk) {
            Transaction::insert($chunk);
        }

        // Upsert stocks from IN transactions
        $stockRecords = [];
        $now = now();
        foreach ($stockMap as $stock) {
            $existing = Stock::where('kode_barang', $stock['kode_barang'])
                ->where('location', $stock['location'])
                ->first();

            if ($existing) {
                $existing->increment('qty', $stock['qty']);
            } else {
                $stockRecords[] = [
                    'kode_barang' => $stock['kode_barang'],
                    'qty'         => $stock['qty'],
                    'location'    => $stock['location'],
                    'box'         => $stock['box'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        foreach (array_chunk($stockRecords, 100) as $chunk) {
            Stock::insert($chunk);
        }

        $this->command->info('Seeded 200 IN transactions and updated stocks.');

        // --- 200 Transaction OUT ---
        // Pick 200 stocked products (prefer products with enough qty)
        $stockedKodes = Stock::where('qty', '>=', 5)->pluck('kode_barang')->toArray();
        shuffle($stockedKodes);
        $outKodes = array_slice($stockedKodes, 0, min(200, count($stockedKodes)));

        $outTransactions = [];

        foreach ($outKodes as $index => $kodeBarang) {
            $stock = Stock::where('kode_barang', $kodeBarang)->where('qty', '>', 0)->first();
            if (!$stock) {
                continue;
            }

            $maxOut = min(10, $stock->qty);
            $qty    = rand(1, $maxOut);

            $sessionId = 'SESS-OUT-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $outTransactions[] = [
                'session_id'  => $sessionId,
                'kode_barang' => $kodeBarang,
                'qty'         => $qty,
                'location'    => $stock->location,
                'box'         => $stock->box,
                'status'      => 'OK',
                'type'        => 'OUT',
                'remarks'     => 'Sample outbound - sales fulfilment',
                'created_at'  => now()->subDays(rand(1, 29))->subHours(rand(0, 23)),
                'updated_at'  => now()->subDays(rand(1, 29)),
            ];

            $stock->decrement('qty', $qty);
        }

        foreach (array_chunk($outTransactions, 100) as $chunk) {
            Transaction::insert($chunk);
        }

        $this->command->info('Seeded ' . count($outTransactions) . ' OUT transactions and decremented stocks.');
    }
}
