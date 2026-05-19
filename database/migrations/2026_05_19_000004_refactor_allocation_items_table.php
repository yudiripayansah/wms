<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('allocation_items', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('allocation_id');
            $table->string('bin')->nullable()->after('location');
        });

        DB::table('allocation_items')->get()->each(function ($item) {
            $inventory = DB::table('inventories')
                ->where('article', $item->kode_barang)
                ->first();

            DB::table('allocation_items')->where('id', $item->id)->update([
                'barcode' => $inventory?->barcode ?? $item->kode_barang,
                'bin'     => $item->box,
            ]);
        });

        Schema::table('allocation_items', function (Blueprint $table) {
            $table->dropForeign(['kode_barang']);
            $table->dropColumn(['kode_barang', 'box']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('allocation_items', function (Blueprint $table) {
                $table->foreign('barcode')->references('barcode')->on('inventories')->onDelete('cascade');
            });
        }

        Schema::enableForeignKeyConstraints();

        // ── Drop products table (data now in inventories) ────────────────
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('products');
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Restore products table first
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->string('brand')->nullable();
            $table->string('barcode')->nullable();
            $table->string('sku')->nullable();
            $table->string('nama_barang');
            $table->string('colour')->nullable();
            $table->string('size')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
        });

        DB::table('inventories')->get()->each(function ($inv) {
            DB::table('products')->insertOrIgnore([
                'kode_barang' => $inv->article ?? $inv->barcode,
                'brand'       => $inv->brand,
                'barcode'     => $inv->barcode,
                'sku'         => $inv->sku,
                'nama_barang' => $inv->article ?? $inv->barcode,
                'colour'      => $inv->color,
                'size'        => $inv->size,
                'price'       => 0,
                'created_at'  => $inv->created_at,
                'updated_at'  => $inv->updated_at,
            ]);
        });

        Schema::disableForeignKeyConstraints();

        Schema::table('allocation_items', function (Blueprint $table) {
            $table->string('kode_barang')->nullable()->after('allocation_id');
            $table->string('box')->nullable()->after('location');
        });

        DB::table('allocation_items')->get()->each(function ($item) {
            $inventory = DB::table('inventories')->where('barcode', $item->barcode)->first();
            DB::table('allocation_items')->where('id', $item->id)->update([
                'kode_barang' => $inventory?->article ?? $item->barcode,
                'box'         => $item->bin,
            ]);
        });

        Schema::table('allocation_items', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'bin']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
