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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();

            $table->string('kode_barang');
            $table->foreign('kode_barang')->references('kode_barang')->on('products')->onDelete('cascade');

            $table->integer('qty');
            $table->string('location')->nullable();
            $table->string('box')->nullable();

            $table->enum('status', ['OK', 'DECLINED'])->default('OK');
            $table->enum('type', ['IN', 'OUT', 'OPNAME']);

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
