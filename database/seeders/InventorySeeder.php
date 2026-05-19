<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Nike' => [
                'code' => 'NKE',
                'models' => [
                    ['name' => 'Air Max 90', 'code' => 'AM90'],
                    ['name' => 'Air Force 1 Low', 'code' => 'AF1L'],
                    ['name' => 'Air Jordan 1 Retro', 'code' => 'AJ1R'],
                    ['name' => 'React Infinity Run', 'code' => 'REI'],
                    ['name' => 'Pegasus 40', 'code' => 'PEG40'],
                    ['name' => 'Free Run 5.0', 'code' => 'FR5'],
                    ['name' => 'Blazer Mid 77', 'code' => 'BLZ77'],
                    ['name' => 'Cortez Basic', 'code' => 'CTZ'],
                    ['name' => 'Dunk Low', 'code' => 'DNKL'],
                    ['name' => 'Waffle Trainer 2', 'code' => 'WFL2'],
                ],
            ],
            'Adidas' => [
                'code' => 'ADS',
                'models' => [
                    ['name' => 'Ultraboost 23', 'code' => 'UB23'],
                    ['name' => 'Stan Smith', 'code' => 'STN'],
                    ['name' => 'Superstar', 'code' => 'SST'],
                    ['name' => 'NMD R1', 'code' => 'NMDR1'],
                    ['name' => 'Gazelle Indoor', 'code' => 'GAZ'],
                    ['name' => 'Samba OG', 'code' => 'SAM'],
                    ['name' => 'Forum Low', 'code' => 'FRML'],
                    ['name' => 'ZX 8000', 'code' => 'ZX8K'],
                    ['name' => 'Handball Spezial', 'code' => 'HBS'],
                    ['name' => 'Campus 00s', 'code' => 'CPS'],
                ],
            ],
            'Puma' => [
                'code' => 'PMA',
                'models' => [
                    ['name' => 'Suede Classic XXI', 'code' => 'SDE'],
                    ['name' => 'RS-X Puzzle', 'code' => 'RSX'],
                    ['name' => 'Clyde All-Pro', 'code' => 'CLY'],
                    ['name' => 'Future Rider', 'code' => 'FTRR'],
                    ['name' => 'King Top', 'code' => 'KNG'],
                    ['name' => 'Roma Basic', 'code' => 'ROM'],
                    ['name' => 'Basket Classic', 'code' => 'BSK'],
                    ['name' => 'Drift Cat 5', 'code' => 'DFT5'],
                    ['name' => 'Cell Viper', 'code' => 'CELV'],
                    ['name' => 'Smash v2', 'code' => 'SMH2'],
                ],
            ],
            'New Balance' => [
                'code' => 'NBL',
                'models' => [
                    ['name' => '574 Core', 'code' => '574C'],
                    ['name' => '990v6', 'code' => '990V6'],
                    ['name' => '327', 'code' => '327'],
                    ['name' => '550', 'code' => '550'],
                    ['name' => '1080v12', 'code' => '1080'],
                    ['name' => 'Fresh Foam X 1080', 'code' => 'FFX'],
                    ['name' => 'Hierarchy v1', 'code' => 'HRC'],
                    ['name' => '530', 'code' => '530'],
                    ['name' => 'Numeric 508', 'code' => 'N508'],
                    ['name' => '2002R', 'code' => '2002R'],
                ],
            ],
            'Converse' => [
                'code' => 'CVS',
                'models' => [
                    ['name' => 'Chuck Taylor All Star Low', 'code' => 'CTAL'],
                    ['name' => 'Chuck 70 High', 'code' => 'C70H'],
                    ['name' => 'One Star Pro', 'code' => 'OSP'],
                    ['name' => 'Pro Leather Low', 'code' => 'PRL'],
                    ['name' => 'Jack Purcell', 'code' => 'JPR'],
                    ['name' => 'Run Star Hike', 'code' => 'RSH'],
                    ['name' => 'Star Player 76', 'code' => 'SP76'],
                    ['name' => 'Breakpoint Pro', 'code' => 'BKP'],
                    ['name' => 'Louie Lopez Pro', 'code' => 'LLP'],
                    ['name' => 'AS-1 Pro', 'code' => 'AS1P'],
                ],
            ],
            'Vans' => [
                'code' => 'VNS',
                'models' => [
                    ['name' => 'Old Skool', 'code' => 'OLS'],
                    ['name' => 'Sk8-Hi', 'code' => 'SK8H'],
                    ['name' => 'Authentic', 'code' => 'ATH'],
                    ['name' => 'Era', 'code' => 'ERA'],
                    ['name' => 'Slip-On 47 V DX', 'code' => 'SLND'],
                    ['name' => 'Half Cab Pro', 'code' => 'HCP'],
                    ['name' => 'Chukka Low', 'code' => 'CHK'],
                    ['name' => 'Checkerboard Old Skool', 'code' => 'CBOS'],
                    ['name' => 'Pro Skate Berle', 'code' => 'PSB'],
                    ['name' => 'Ward Low', 'code' => 'WRD'],
                ],
            ],
            'Reebok' => [
                'code' => 'RBK',
                'models' => [
                    ['name' => 'Classic Leather', 'code' => 'CLS'],
                    ['name' => 'Club C 85', 'code' => 'CC85'],
                    ['name' => 'Freestyle Hi', 'code' => 'FSH'],
                    ['name' => 'Nano X3', 'code' => 'NX3'],
                    ['name' => 'Instapump Fury', 'code' => 'IPF'],
                    ['name' => 'Floatride Energy 5', 'code' => 'FLE5'],
                    ['name' => 'Zig Kinetica 2.5', 'code' => 'ZGK'],
                    ['name' => 'Kamikaze II', 'code' => 'KMK2'],
                    ['name' => 'Legacy 83', 'code' => 'LG83'],
                    ['name' => 'Answer IV', 'code' => 'ANS4'],
                ],
            ],
            'Skechers' => [
                'code' => 'SKC',
                'models' => [
                    ['name' => "D'Lites Chunky", 'code' => 'DLT'],
                    ['name' => 'Max Cushioning Elite', 'code' => 'MCE'],
                    ['name' => 'Arch Fit', 'code' => 'ARF'],
                    ['name' => 'Go Walk 6', 'code' => 'GW6'],
                    ['name' => 'Glide-Step Sport', 'code' => 'GSS'],
                    ['name' => 'Stamina Airy', 'code' => 'STA'],
                    ['name' => 'Equalizer 5.0', 'code' => 'EQ5'],
                    ['name' => 'Foamies Cali Gear', 'code' => 'FCG'],
                    ['name' => 'Flex Advantage 4.0', 'code' => 'FA4'],
                    ['name' => 'Elite Flex', 'code' => 'ELF'],
                ],
            ],
            'Asics' => [
                'code' => 'ASC',
                'models' => [
                    ['name' => 'Gel Nimbus 25', 'code' => 'GN25'],
                    ['name' => 'Gel Kayano 30', 'code' => 'GK30'],
                    ['name' => 'Gel Cumulus 25', 'code' => 'GC25'],
                    ['name' => 'GT 2000 12', 'code' => 'GT12'],
                    ['name' => 'Gel Venture 9', 'code' => 'GV9'],
                    ['name' => 'Fuji Trabuco Max 2', 'code' => 'FTM2'],
                    ['name' => 'Quantum 360 VII', 'code' => 'Q360'],
                    ['name' => 'Gel Resolution 9', 'code' => 'GR9'],
                    ['name' => 'Gel Trabuco 11', 'code' => 'GTB11'],
                    ['name' => 'Gel Contend 8', 'code' => 'GCT8'],
                ],
            ],
            'Under Armour' => [
                'code' => 'UAR',
                'models' => [
                    ['name' => 'HOVR Phantom 3', 'code' => 'HP3'],
                    ['name' => 'Charged Assert 10', 'code' => 'CA10'],
                    ['name' => 'Flow Velociti Elite', 'code' => 'FVE'],
                    ['name' => 'Micro G Pursuit', 'code' => 'MGP'],
                    ['name' => 'SpeedForm Amp', 'code' => 'SFA'],
                    ['name' => 'Forge 96', 'code' => 'F96'],
                    ['name' => 'Spawn 3', 'code' => 'SP3'],
                    ['name' => 'Tribase Reign 5', 'code' => 'TBR5'],
                    ['name' => 'Verge 2 Low', 'code' => 'VG2L'],
                    ['name' => 'Slip Speed', 'code' => 'SLSP'],
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

        $inventories    = [];
        $barcodeCounter = 8901234000001;
        $sizeIndex      = 0;
        $now            = now();

        foreach ($brands as $brandName => $brandData) {
            foreach ($brandData['models'] as $model) {
                foreach ($colors as $colorCode => $colorName) {
                    $size = $sizes[$sizeIndex % count($sizes)];
                    $sizeIndex++;

                    $article = "{$brandName} {$model['name']} {$colorName} Size {$size}";
                    $sku     = "{$brandData['code']}-{$model['code']}-{$colorCode}-{$size}";

                    $inventories[] = [
                        'barcode'    => (string) $barcodeCounter++,
                        'brand'      => $brandName,
                        'sku'        => $sku,
                        'article'    => $article,
                        'color'      => $colorName,
                        'size'       => (string) $size,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($inventories, 100) as $chunk) {
            Inventory::insert($chunk);
        }

        $this->command->info('Seeded ' . count($inventories) . ' inventory items.');
    }
}
