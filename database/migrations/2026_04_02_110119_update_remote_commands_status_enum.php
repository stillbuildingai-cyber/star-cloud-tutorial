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
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE remote_commands MODIFY COLUMN status ENUM('pending', 'sent', 'success', 'failed', 'superseded') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE remote_commands MODIFY COLUMN status ENUM('pending', 'sent', 'success', 'failed') DEFAULT 'pending'");
        }
    }
};
