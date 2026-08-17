<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE machine_stock_movements MODIFY COLUMN type ENUM('replenishment', 'pickup', 'remote_dispense', 'adjustment', 'rollback', 'sale', 'decommission') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE machine_stock_movements MODIFY COLUMN type ENUM('replenishment', 'pickup', 'remote_dispense', 'adjustment', 'rollback') NOT NULL");
        }
    }
};
