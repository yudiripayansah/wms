<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // In MySQL, SET col1 = col2, col2 = col1 reads ORIGINAL values before any write,
        // so this is a safe atomic swap without a temp column.
        DB::statement('UPDATE stocks          SET location = bin, bin = location');
        DB::statement('UPDATE transactions    SET location = bin, bin = location');
        DB::statement('UPDATE allocation_items SET location = bin, bin = location');
    }

    public function down(): void
    {
        // Swapping twice restores original state
        DB::statement('UPDATE stocks          SET location = bin, bin = location');
        DB::statement('UPDATE transactions    SET location = bin, bin = location');
        DB::statement('UPDATE allocation_items SET location = bin, bin = location');
    }
};
