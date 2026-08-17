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
        Schema::table('machines', function (Blueprint $table) {
            if (!Schema::hasColumn('machines', 'ambient_temp_monitoring_enabled')) {
                $table->boolean('ambient_temp_monitoring_enabled')->default(false)->after('ambient_temp_setting')->comment('是否啟用環境溫度監測');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('ambient_temp_monitoring_enabled');
        });
    }
};
