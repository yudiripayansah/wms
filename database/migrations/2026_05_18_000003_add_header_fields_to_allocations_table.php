<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('allocations', function (Blueprint $table) {
            $table->string('customer')->nullable()->after('remarks');
            $table->string('distribution')->nullable()->after('customer');
            $table->string('release_date')->nullable()->after('distribution');
            $table->string('brand')->nullable()->after('release_date');
            $table->string('sales_associate')->nullable()->after('brand');
            $table->string('route')->nullable()->after('sales_associate');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::table('allocations', function (Blueprint $table) {
            $table->dropColumn(['customer', 'distribution', 'release_date', 'brand', 'sales_associate', 'route']);
        });
        Schema::enableForeignKeyConstraints();
    }
};
