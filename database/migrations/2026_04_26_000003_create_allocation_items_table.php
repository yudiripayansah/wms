<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allocation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allocation_id')->constrained()->cascadeOnDelete();
            $table->string('kode_barang');
            $table->foreign('kode_barang')->references('kode_barang')->on('products')->onDelete('cascade');
            $table->integer('qty');
            $table->string('location')->nullable();
            $table->string('box')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocation_items');
    }
};
