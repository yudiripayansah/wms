<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Nike' => [
                'code' => 'NKE',
                'models' => [
                    ['name' => 'Air Max 90', 'code' => 'AM90', 'price' => 1500000],
                    ['name' => 'Air Force 1 Low', 'code' => 'AF1L', 'price' => 1300000],
                    ['name' => 'Air Jordan 1 Retro', 'code' => 'AJ1R', 'price' => 2000000],
                    ['name' => 'React Infinity Run', 'code' => 'REI', 'price' => 1800000],
                    ['name' => 'Pegasus 40', 'code' => 'PEG40', 'price' => 1600000],
                    ['name' => 'Free Run 5.0', 'code' => 'FR5', 'price' => 1200000],
                    ['name' => 'Blazer Mid 77', 'code' => 'BLZ77', 'price' => 1400000],
                    ['name' => 'Cortez Basic', 'code' => 'CTZ', 'price' => 1100000],
                    ['name' => 'Dunk Low', 'code' => 'DNKL', 'price' => 1700000],
                    ['name' => 'Waffle Trainer 2', 'code' => 'WFL2', 'price' => 1350000],
                ],
            ],
            'Adidas' => [
                'code' => 'ADS',
                'models' => [
                    ['name' => 'Ultraboost 23', 'code' => 'UB23', 'price' => 2200000],
                    ['name' => 'Stan Smith', 'code' => 'STN', 'price' => 1200000],
                    ['name' => 'Superstar', 'code' => 'SST', 'price' => 1100000],
                    ['name' => 'NMD R1', 'code' => 'NMDR1', 'price' => 1800000],
                    ['name' => 'Gazelle Indoor', 'code' => 'GAZ', 'price' => 1300000],
                    ['name' => 'Samba OG', 'code' => 'SAM', 'price' => 1400000],
                    ['name' => 'Forum Low', 'code' => 'FRML', 'price' => 1250000],
                    ['name' => 'ZX 8000', 'code' => 'ZX8K', 'price' => 1600000],
                    ['name' => 'Handball Spezial', 'code' => 'HBS', 'price' => 1350000],
                    ['name' => 'Campus 00s', 'code' => 'CPS', 'price' => 1200000],
                ],
            ],
            'Puma' => [
                'code' => 'PMA',
                'models' => [
                    ['name' => 'Suede Classic XXI', 'code' => 'SDE', 'price' => 850000],
                    ['name' => 'RS-X Puzzle', 'code' => 'RSX', 'price' => 1100000],
                    ['name' => 'Clyde All-Pro', 'code' => 'CLY', 'price' => 950000],
                    ['name' => 'Future Rider', 'code' => 'FTRR', 'price' => 900000],
                    ['name' => 'King Top', 'code' => 'KNG', 'price' => 800000],
                    ['name' => 'Roma Basic', 'code' => 'ROM', 'price' => 750000],
                    ['name' => 'Basket Classic', 'code' => 'BSK', 'price' => 700000],
                    ['name' => 'Drift Cat 5', 'code' => 'DFT5', 'price' => 650000],
                    ['name' => 'Cell Viper', 'code' => 'CELV', 'price' => 900000],
                    ['name' => 'Smash v2', 'code' => 'SMH2', 'price' => 600000],
                ],
            ],
            'New Balance' => [
                'code' => 'NBL',
                'models' => [
                    ['name' => '574 Core', 'code' => '574C', 'price' => 1400000],
                    ['name' => '990v6', 'code' => '990V6', 'price' => 2400000],
                    ['name' => '327', 'code' => '327', 'price' => 1300000],
                    ['name' => '550', 'code' => '550', 'price' => 1500000],
                    ['name' => '1080v12', 'code' => '1080', 'price' => 2000000],
                    ['name' => 'Fresh Foam X 1080', 'code' => 'FFX', 'price' => 1700000],
                    ['name' => 'Hierarchy v1', 'code' => 'HRC', 'price' => 1100000],
                    ['name' => '530', 'code' => '530', 'price' => 1200000],
                    ['name' => 'Numeric 508', 'code' => 'N508', 'price' => 1000000],
                    ['name' => '2002R', 'code' => '2002R', 'price' => 1600000],
                ],
            ],
            'Converse' => [
                'code' => 'CVS',
                'models' => [
                    ['name' => 'Chuck Taylor All Star Low', 'code' => 'CTAL', 'price' => 650000],
                    ['name' => 'Chuck 70 High', 'code' => 'C70H', 'price' => 850000],
                    ['name' => 'One Star Pro', 'code' => 'OSP', 'price' => 750000],
                    ['name' => 'Pro Leather Low', 'code' => 'PRL', 'price' => 900000],
                    ['name' => 'Jack Purcell', 'code' => 'JPR', 'price' => 700000],
                    ['name' => 'Run Star Hike', 'code' => 'RSH', 'price' => 1100000],
                    ['name' => 'Star Player 76', 'code' => 'SP76', 'price' => 600000],
                    ['name' => 'Breakpoint Pro', 'code' => 'BKP', 'price' => 700000],
                    ['name' => 'Louie Lopez Pro', 'code' => 'LLP', 'price' => 800000],
                    ['name' => 'AS-1 Pro', 'code' => 'AS1P', 'price' => 900000],
                ],
            ],
            'Vans' => [
                'code' => 'VNS',
                'models' => [
                    ['name' => 'Old Skool', 'code' => 'OLS', 'price' => 750000],
                    ['name' => 'Sk8-Hi', 'code' => 'SK8H', 'price' => 850000],
                    ['name' => 'Authentic', 'code' => 'ATH', 'price' => 550000],
                    ['name' => 'Era', 'code' => 'ERA', 'price' => 600000],
                    ['name' => 'Slip-On 47 V DX', 'code' => 'SLND', 'price' => 500000],
                    ['name' => 'Half Cab Pro', 'code' => 'HCP', 'price' => 800000],
                    ['name' => 'Chukka Low', 'code' => 'CHK', 'price' => 650000],
                    ['name' => 'Checkerboard Old Skool', 'code' => 'CBOS', 'price' => 700000],
                    ['name' => 'Pro Skate Berle', 'code' => 'PSB', 'price' => 900000],
                    ['name' => 'Ward Low', 'code' => 'WRD', 'price' => 600000],
                ],
            ],
            'Reebok' => [
                'code' => 'RBK',
                'models' => [
                    ['name' => 'Classic Leather', 'code' => 'CLS', 'price' => 900000],
                    ['name' => 'Club C 85', 'code' => 'CC85', 'price' => 800000],
                    ['name' => 'Freestyle Hi', 'code' => 'FSH', 'price' => 750000],
                    ['name' => 'Nano X3', 'code' => 'NX3', 'price' => 1600000],
                    ['name' => 'Instapump Fury', 'code' => 'IPF', 'price' => 1800000],
                    ['name' => 'Floatride Energy 5', 'code' => 'FLE5', 'price' => 1200000],
                    ['name' => 'Zig Kinetica 2.5', 'code' => 'ZGK', 'price' => 1400000],
                    ['name' => 'Kamikaze II', 'code' => 'KMK2', 'price' => 1100000],
                    ['name' => 'Legacy 83', 'code' => 'LG83', 'price' => 950000],
                    ['name' => 'Answer IV', 'code' => 'ANS4', 'price' => 1300000],
                ],
            ],
            'Skechers' => [
                'code' => 'SKC',
                'models' => [
                    ['name' => "D'Lites Chunky", 'code' => 'DLT', 'price' => 800000],
                    ['name' => 'Max Cushioning Elite', 'code' => 'MCE', 'price' => 900000],
                    ['name' => 'Arch Fit', 'code' => 'ARF', 'price' => 1000000],
                    ['name' => 'Go Walk 6', 'code' => 'GW6', 'price' => 750000],
                    ['name' => 'Glide-Step Sport', 'code' => 'GSS', 'price' => 700000],
                    ['name' => 'Stamina Airy', 'code' => 'STA', 'price' => 650000],
                    ['name' => 'Equalizer 5.0', 'code' => 'EQ5', 'price' => 850000],
                    ['name' => 'Foamies Cali Gear', 'code' => 'FCG', 'price' => 550000],
                    ['name' => 'Flex Advantage 4.0', 'code' => 'FA4', 'price' => 700000],
                    ['name' => 'Elite Flex', 'code' => 'ELF', 'price' => 750000],
                ],
            ],
            'Asics' => [
                'code' => 'ASC',
                'models' => [
                    ['name' => 'Gel Nimbus 25', 'code' => 'GN25', 'price' => 2000000],
                    ['name' => 'Gel Kayano 30', 'code' => 'GK30', 'price' => 2200000],
                    ['name' => 'Gel Cumulus 25', 'code' => 'GC25', 'price' => 1800000],
                    ['name' => 'GT 2000 12', 'code' => 'GT12', 'price' => 1600000],
                    ['name' => 'Gel Venture 9', 'code' => 'GV9', 'price' => 900000],
                    ['name' => 'Fuji Trabuco Max 2', 'code' => 'FTM2', 'price' => 1700000],
                    ['name' => 'Quantum 360 VII', 'code' => 'Q360', 'price' => 1400000],
                    ['name' => 'Gel Resolution 9', 'code' => 'GR9', 'price' => 1900000],
                    ['name' => 'Gel Trabuco 11', 'code' => 'GTB11', 'price' => 1500000],
                    ['name' => 'Gel Contend 8', 'code' => 'GCT8', 'price' => 800000],
                ],
            ],
            'Under Armour' => [
                'code' => 'UAR',
                'models' => [
                    ['name' => 'HOVR Phantom 3', 'code' => 'HP3', 'price' => 1800000],
                    ['name' => 'Charged Assert 10', 'code' => 'CA10', 'price' => 900000],
                    ['name' => 'Flow Velociti Elite', 'code' => 'FVE', 'price' => 2000000],
                    ['name' => 'Micro G Pursuit', 'code' => 'MGP', 'price' => 1000000],
                    ['name' => 'SpeedForm Amp', 'code' => 'SFA', 'price' => 1100000],
                    ['name' => 'Forge 96', 'code' => 'F96', 'price' => 1400000],
                    ['name' => 'Spawn 3', 'code' => 'SP3', 'price' => 1300000],
                    ['name' => 'Tribase Reign 5', 'code' => 'TBR5', 'price' => 1600000],
                    ['name' => 'Verge 2 Low', 'code' => 'VG2L', 'price' => 1200000],
                    ['name' => 'Slip Speed', 'code' => 'SLSP', 'price' => 800000],
                ],
            ],
        ];

        $colors = [
            'BLK' => 'Black',
            'WHT' => 'White',
            'RED' => 'Red',
            'BLU' => 'Blue',
            'GRY' => 'Grey',
            'NVY' => 'Navy',
            'GRN' => 'Green',
            'BRN' => 'Brown',
            'ORG' => 'Orange',
            'YLW' => 'Yellow',
        ];

        $sizes = [37, 38, 39, 40, 41, 42, 43, 44];

        $products = [];
        $barcodeCounter = 8901234000001;
        $sizeIndex = 0;
        $now = now();

        foreach ($brands as $brandName => $brandData) {
            foreach ($brandData['models'] as $model) {
                foreach ($colors as $colorCode => $colorName) {
                    $size = $sizes[$sizeIndex % count($sizes)];
                    $sizeIndex++;

                    $kode = "{$brandData['code']}-{$model['code']}-{$colorCode}-{$size}";

                    $priceVariation = (int) round(($model['price'] * (rand(-10, 15) / 100)) / 1000) * 1000;
                    $finalPrice = $model['price'] + $priceVariation;

                    $products[] = [
                        'kode_barang' => $kode,
                        'brand'       => $brandName,
                        'barcode'     => (string) $barcodeCounter++,
                        'sku'         => $kode,
                        'nama_barang' => "{$brandName} {$model['name']} {$colorName} Size {$size}",
                        'colour'      => $colorName,
                        'size'        => (string) $size,
                        'price'       => $finalPrice,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($products, 100) as $chunk) {
            Product::insert($chunk);
        }

        $this->command->info('Seeded ' . count($products) . ' shoe products.');
    }
}
