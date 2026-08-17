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
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')
                  ->constrained('companies')->onDelete('cascade');
            
            // 移除舊有的唯一鍵
            $table->dropUnique('roles_name_guard_name_unique');
            // 新增複合唯一鍵 (涵蓋租戶)
            $table->unique(['name', 'guard_name', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name', 'company_id']);
            $table->unique(['name', 'guard_name']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
