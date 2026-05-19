<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remap existing records before changing the column definition
        DB::table('allocations')->where('status', 'DRAFT')->update(['status' => 'PENDING']);
        DB::table('allocations')->where('status', 'CONFIRMED')->update(['status' => 'PROCESSING']);
        DB::table('allocations')->where('status', 'PROCESSED')->update(['status' => 'FINISHED']);

        DB::statement("ALTER TABLE `allocations` MODIFY COLUMN `status` ENUM('PENDING','PROCESSING','FINISHED','COMPLETED') NOT NULL DEFAULT 'PENDING'");
    }

    public function down(): void
    {
        DB::table('allocations')->where('status', 'PENDING')->update(['status' => 'DRAFT']);
        DB::table('allocations')->where('status', 'PROCESSING')->update(['status' => 'CONFIRMED']);
        DB::table('allocations')->where('status', 'FINISHED')->update(['status' => 'PROCESSED']);
        DB::table('allocations')->where('status', 'COMPLETED')->update(['status' => 'PROCESSED']);

        DB::statement("ALTER TABLE `allocations` MODIFY COLUMN `status` ENUM('DRAFT','CONFIRMED','PROCESSED') NOT NULL DEFAULT 'DRAFT'");
    }
};
