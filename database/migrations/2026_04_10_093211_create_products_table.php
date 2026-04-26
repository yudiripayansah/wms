<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique();
            $table->string('brand')->nullable();
            $table->string('barcode')->nullable();
            $table->string('sku')->nullable();
            $table->string('nama_barang');
            $table->string('colour')->nullable();
            $table->string('size')->nullable();
            $table->integer('qty')->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->string('location')->nullable();
            $table->string('box')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
