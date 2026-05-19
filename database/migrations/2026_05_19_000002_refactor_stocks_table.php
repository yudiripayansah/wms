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

        // ── Add new columns ──────────────────────────────────────────────
        Schema::table('stocks', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('id');
            $table->string('bin')->nullable()->after('location');
        });

        // ── Migrate data: kode_barang → barcode (via inventories.article)
        //    and box → bin
        DB::table('stocks')->get()->each(function ($stock) {
            $inventory = DB::table('inventories')
                ->where('article', $stock->kode_barang)
                ->first();

            DB::table('stocks')->where('id', $stock->id)->update([
                'barcode' => $inventory?->barcode ?? $stock->kode_barang,
                'bin'     => $stock->box,
            ]);
        });

        // ── Drop old columns ─────────────────────────────────────────────
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['kode_barang']);
            $table->dropColumn(['kode_barang', 'box']);
        });

        // ── Add FK constraint ────────────────────────────────────────────
        // Only on drivers that support it properly
        if (DB::getDriverName() === 'mysql') {
            Schema::table('stocks', function (Blueprint $table) {
                $table->foreign('barcode')->references('barcode')->on('inventories')->onDelete('cascade');
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('stocks', function (Blueprint $table) {
            if (DB::getDriverName() === 'mysql') {
                $table->dropForeignIdFor(\App\Models\Inventory::class, 'barcode');
            }
            $table->string('kode_barang')->nullable()->after('id');
            $table->string('box')->nullable()->after('location');
        });

        DB::table('stocks')->get()->each(function ($stock) {
            $inventory = DB::table('inventories')->where('barcode', $stock->barcode)->first();
            DB::table('stocks')->where('id', $stock->id)->update([
                'kode_barang' => $inventory?->article ?? $stock->barcode,
                'box'         => $stock->bin,
            ]);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'bin']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
