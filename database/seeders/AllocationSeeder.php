<?php

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\AllocationItem;
use App\Models\Stock;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class AllocationSeeder extends Seeder
{
    private array $customers = [
        'PT. Matahari Dept Store',
        'PT. Ramayana Lestari',
        'PT. Trans Retail Indonesia',
        'Toko Sepatu Jaya',
        'CV. Mandiri Niaga',
        'PT. Indomarco Prismatama',
        'PT. Sumber Alfaria Trijaya',
        'PT. Hero Supermarket',
        'PT. Sri Ratu Pemuda',
        'Giant Hypermarket',
        'PT. Carrefour Indonesia',
        'UD. Makmur Bersama',
        'Hypermart Kelapa Gading',
        'PT. Erafone Artha Retailindo',
        'PT. Mitra Adi Perkasa',
    ];

    private array $brands = ['Nike', 'Adidas', 'Puma', 'New Balance', 'Converse', 'Vans', 'Reebok', 'Skechers'];

    private array $salesAssociates = [
        'Budi Santoso', 'Siti Rahayu', 'Ahmad Fauzi', 'Dewi Permata',
        'Randi Pratama', 'Yunita Sari', 'Hendra Gunawan', 'Lilis Suryani',
    ];

    private array $routes = [
        'Jakarta Utara', 'Jakarta Selatan', 'Jakarta Barat', 'Jakarta Timur',
        'Surabaya', 'Bandung', 'Medan', 'Makassar', 'Semarang', 'Yogyakarta',
    ];

    public function run(): void
    {
        $admin = User::where('email', 'admin@wms.com')->first();

        $stockItems = Stock::where('qty', '>=', 3)
            ->inRandomOrder()
            ->limit(100)
            ->get();

        if ($stockItems->isEmpty()) {
            $this->command->warn('No stock data found. Run TransactionSeeder first.');
            return;
        }

        $statuses = [
            ['status' => 'PENDING',    'count' => 5, 'itemsPer' => 3, 'createOut' => false],
            ['status' => 'PROCESSING', 'count' => 5, 'itemsPer' => 4, 'createOut' => false],
            ['status' => 'FINISHED',   'count' => 5, 'itemsPer' => 5, 'createOut' => false],
            ['status' => 'COMPLETED',  'count' => 5, 'itemsPer' => 5, 'createOut' => true],
        ];

        $offset = 0;

        foreach ($statuses as $s) {
            $prefix = match ($s['status']) {
                'PENDING'    => 'PE',
                'PROCESSING' => 'PR',
                'FINISHED'   => 'FN',
                'COMPLETED'  => 'CO',
                default      => 'XX',
            };

            for ($i = 1; $i <= $s['count']; $i++) {
                $sessionId = 'ALLOC-' . $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);

                $daysAgo = match ($s['status']) {
                    'PENDING'    => rand(1, 7),
                    'PROCESSING' => rand(3, 14),
                    'FINISHED'   => rand(7, 21),
                    'COMPLETED'  => rand(14, 45),
                    default      => rand(1, 30),
                };

                $allocation = Allocation::create([
                    'user_id'         => $admin?->id,
                    'session_id'      => $sessionId,
                    'status'          => $s['status'],
                    'customer'        => $this->customers[array_rand($this->customers)],
                    'distribution'    => 'DC-' . chr(64 + $i),
                    'release_date'    => now()->addDays(rand(7, 60))->format('d/m/Y'),
                    'brand'           => $this->brands[array_rand($this->brands)],
                    'sales_associate' => $this->salesAssociates[array_rand($this->salesAssociates)],
                    'route'           => $this->routes[array_rand($this->routes)],
                    'remarks'         => $i % 3 === 0 ? 'Priority — handle with care' : null,
                    'created_at'      => now()->subDays($daysAgo)->subHours(rand(0, 8)),
                    'updated_at'      => now()->subDays(max(0, $daysAgo - 1)),
                ]);

                $slice      = $stockItems->slice($offset, $s['itemsPer'])->values();
                $offset    += $s['itemsPer'];
                $sessionOut = 'OUT-' . $sessionId;

                foreach ($slice as $stockItem) {
                    $maxQty = max(1, (int) floor($stockItem->qty / 2));
                    $qty    = min(rand(1, 5), $maxQty);

                    AllocationItem::create([
                        'allocation_id' => $allocation->id,
                        'barcode'       => $stockItem->barcode,
                        'qty'           => $qty,
                        'location'      => $stockItem->location,
                        'bin'           => $stockItem->bin,
                    ]);

                    if ($s['createOut']) {
                        Transaction::create([
                            'session_id' => $sessionOut,
                            'barcode'    => $stockItem->barcode,
                            'qty'        => $qty,
                            'location'   => $stockItem->location,
                            'bin'        => $stockItem->bin,
                            'type'       => 'OUT',
                            'status'     => 'OK',
                            'remarks'    => 'Allocation: ' . $sessionId,
                            'created_at' => now()->subDays(max(0, $daysAgo - 2))->subHours(rand(0, 4)),
                            'updated_at' => now()->subDays(max(0, $daysAgo - 2)),
                        ]);

                        // Decrement stock to reflect the completed OUT
                        $stock = Stock::where('barcode', $stockItem->barcode)
                            ->where('location', $stockItem->location)
                            ->where('bin', $stockItem->bin)
                            ->first();

                        if ($stock && $stock->qty >= $qty) {
                            $stock->decrement('qty', $qty);
                        }
                    }
                }
            }

            $this->command->info("Seeded {$s['count']} {$s['status']} allocations.");
        }
    }
}
