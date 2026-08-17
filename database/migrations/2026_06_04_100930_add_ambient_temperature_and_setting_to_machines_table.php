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
            if (!Schema::hasColumn('machines', 'ambient_temperature')) {
                $table->integer('ambient_temperature')->nullable()->after('temperature')->comment('環境溫度');
            }
            if (!Schema::hasColumn('machines', 'ambient_temp_setting')) {
                $table->integer('ambient_temp_setting')->nullable()->after('ambient_temperature')->comment('環境溫度設定值');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['ambient_temperature', 'ambient_temp_setting']);
        });
    }
};
