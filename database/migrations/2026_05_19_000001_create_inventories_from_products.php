<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Create inventories table ──────────────────────────────────
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->unique();
            $table->string('brand')->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('article')->nullable();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->timestamps();
        });

        // ── 2. Migrate data from products → inventories ──────────────────
        // barcode = COALESCE(products.barcode, products.kode_barang) — always unique
        if (Schema::hasTable('products')) {
            $usedBarcodes = [];

            DB::table('products')->get()->each(function ($product) use (&$usedBarcodes) {
                $barcode = ($product->barcode && ! in_array($product->barcode, $usedBarcodes))
                    ? $product->barcode
                    : $product->kode_barang;

                $usedBarcodes[] = $barcode;

                DB::table('inventories')->insertOrIgnore([
                    'barcode'    => $barcode,
                    'brand'      => $product->brand,
                    'sku'        => $product->sku,
                    'article'    => $product->kode_barang,
                    'color'      => $product->colour,
                    'size'       => $product->size,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
