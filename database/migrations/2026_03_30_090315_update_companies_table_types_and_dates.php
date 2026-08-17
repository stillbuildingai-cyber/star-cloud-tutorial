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
        Schema::table('companies', function (Blueprint $table) {
            // 重新命名現有欄位
            $table->renameColumn('valid_until', 'end_date');
            
            // 新增業務類型
            $table->string('original_type')->default('lease')->after('code')->comment('原始類型: buyout, lease');
            $table->string('current_type')->default('lease')->after('original_type')->comment('當前類型: buyout, lease');
            
            // 新增起始日
            $table->date('start_date')->nullable()->after('status')->comment('合約起始日');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->renameColumn('end_date', 'valid_until');
            $table->dropColumn(['original_type', 'current_type', 'start_date']);
        });
    }
};
