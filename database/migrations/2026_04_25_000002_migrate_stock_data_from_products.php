<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing qty/location/box data to stocks table
        DB::table('products')->get()->each(function ($product) {
            $qty = $product->qty ?? 0;
            if ($qty > 0 || !empty($product->location) || !empty($product->box)) {
                DB::table('stocks')->insert([
                    'kode_barang' => $product->kode_barang,
                    'qty'         => $qty,
                    'location'    => $product->location,
                    'box'         => $product->box,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['qty', 'location', 'box']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('qty')->default(0)->after('size');
            $table->string('location')->nullable()->after('price');
            $table->string('box')->nullable()->after('location');
        });
    }
};
