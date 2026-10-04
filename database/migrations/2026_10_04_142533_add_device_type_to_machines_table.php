<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * device_type 讓同一套機隊管理骨架可以同時管理不同種類的裝置，不只智慧插座。
     * 先只加「智慧插座」跟「智慧窗簾」兩種，證明架構本身是通用的，
     * 之後要加其他裝置類型，照同樣的模式加欄位、加指令種類就好，不用動骨架。
     */
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            if (!Schema::hasColumn('machines', 'device_type')) {
                $table->string('device_type', 30)->default('smart_socket')->after('name')->comment('裝置類型：smart_socket、smart_curtain 等');
            }
            if (!Schema::hasColumn('machines', 'curtain_position')) {
                $table->unsignedTinyInteger('curtain_position')->nullable()->after('device_type')->comment('窗簾裝置專用：目前開合位置，0=全關，100=全開');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['device_type', 'curtain_position']);
        });
    }
};
