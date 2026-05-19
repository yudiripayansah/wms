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

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('session_id');
            $table->string('bin')->nullable()->after('location');
        });

        DB::table('transactions')->get()->each(function ($tx) {
            $inventory = DB::table('inventories')
                ->where('article', $tx->kode_barang)
                ->first();

            DB::table('transactions')->where('id', $tx->id)->update([
                'barcode' => $inventory?->barcode ?? $tx->kode_barang,
                'bin'     => $tx->box,
            ]);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['kode_barang']);
            $table->dropColumn(['kode_barang', 'box']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreign('barcode')->references('barcode')->on('inventories')->onDelete('cascade');
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('kode_barang')->nullable()->after('session_id');
            $table->string('box')->nullable()->after('location');
        });

        DB::table('transactions')->get()->each(function ($tx) {
            $inventory = DB::table('inventories')->where('barcode', $tx->barcode)->first();
            DB::table('transactions')->where('id', $tx->id)->update([
                'kode_barang' => $inventory?->article ?? $tx->barcode,
                'box'         => $tx->bin,
            ]);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'bin']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
