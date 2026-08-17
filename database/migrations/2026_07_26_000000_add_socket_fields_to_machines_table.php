<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            if (!Schema::hasColumn('machines', 'machine_id')) {
                $table->string('machine_id')->unique()->nullable()->comment('機台序號/Machine ID');
            }
            if (!Schema::hasColumn('machines', 'socket_ip')) {
                $table->string('socket_ip')->nullable()->comment('智慧插座 IP 位址');
            }
            if (!Schema::hasColumn('machines', 'relay_state')) {
                $table->boolean('relay_state')->default(false)->comment('繼電器狀態 (0:OFF, 1:ON)');
            }
            if (!Schema::hasColumn('machines', 'current_power')) {
                $table->float('current_power')->default(0.0)->comment('即時功率 (W)');
            }
            if (!Schema::hasColumn('machines', 'current_amp')) {
                $table->float('current_amp')->default(0.0)->comment('即時電流 (A)');
            }
            if (!Schema::hasColumn('machines', 'voltage')) {
                $table->float('voltage')->default(110.0)->comment('即時電壓 (V)');
            }
            if (!Schema::hasColumn('machines', 'total_energy')) {
                $table->float('total_energy')->default(0.0)->comment('累計用電量 (kWh)');
            }
            if (!Schema::hasColumn('machines', 'last_telemetry_at')) {
                $table->timestamp('last_telemetry_at')->nullable()->comment('最新心跳時間');
            }
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn([
                'machine_id', 'socket_ip', 'relay_state', 
                'current_power', 'current_amp', 'voltage', 
                'total_energy', 'last_telemetry_at'
            ]);
        });
    }
};
