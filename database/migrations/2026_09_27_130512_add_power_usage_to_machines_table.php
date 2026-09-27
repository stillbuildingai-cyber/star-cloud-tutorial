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
            if (!Schema::hasColumn('machines', 'power_watt')) {
                $table->float('power_watt')->nullable()->after('ambient_temp_setting')->comment('即時功率（瓦特）');
            }
            if (!Schema::hasColumn('machines', 'energy_kwh_total')) {
                $table->float('energy_kwh_total')->nullable()->after('power_watt')->comment('裝置回報的累計用電量（度），單調遞增的計數器');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['power_watt', 'energy_kwh_total']);
        });
    }
};
