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
            $table->integer('card_reader_seconds')->nullable()->default(30)->comment('刷卡機秒數');
            $table->time('card_reader_checkout_time_1')->nullable()->default('22:30:00')->comment('卡機結帳時間1');
            $table->time('card_reader_checkout_time_2')->nullable()->default('23:45:00')->comment('卡機結帳時間2');
            $table->time('heating_start_time')->default('00:00:00')->comment('開啟-加熱時間');
            $table->time('heating_end_time')->default('00:00:00')->comment('關閉-加熱時間');
            $table->integer('payment_buffer_seconds')->nullable()->default(5)->comment('金流緩衝時間(s)');
            $table->string('card_reader_no')->nullable()->comment('刷卡機編號');
            $table->string('key_no')->nullable()->comment('鑰匙編號');
            $table->tinyInteger('invoice_status')->default(0)->comment('發票狀態碼: 0=不開發票, 1=預設捐, 2=預設不捐');
            $table->boolean('welcome_gift_enabled')->default(0)->comment('來店禮開關');
            $table->boolean('is_spring_slot_1_10')->default(0)->comment('貨道類型(1~10): 0=履帶, 1=彈簧');
            $table->boolean('is_spring_slot_11_20')->default(0)->comment('貨道類型(11~20): 0=履帶, 1=彈簧');
            $table->boolean('is_spring_slot_21_30')->default(0)->comment('貨道類型(21~30): 0=履帶, 1=彈簧');
            $table->boolean('is_spring_slot_31_40')->default(0)->comment('貨道類型(31~40): 0=履帶, 1=彈簧');
            $table->boolean('is_spring_slot_41_50')->default(0)->comment('貨道類型(41~50): 0=履帶, 1=彈簧');
            $table->boolean('is_spring_slot_51_60')->default(0)->comment('貨道類型(51~60): 0=履帶, 1=彈簧');
            $table->boolean('member_system_enabled')->default(0)->comment('會員系統開關');
            
            $table->foreignId('payment_config_id')->nullable()->constrained('payment_configs')->nullOnDelete()->comment('關聯金流參數組合');
            $table->foreignId('machine_model_id')->nullable()->constrained('machine_models')->nullOnDelete()->comment('關類型號組合');
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete()->comment('建立者');
            $table->foreignId('updater_id')->nullable()->constrained('users')->nullOnDelete()->comment('修改者');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropForeign(['payment_config_id']);
            $table->dropForeign(['machine_model_id']);
            $table->dropForeign(['creator_id']);
            $table->dropForeign(['updater_id']);
            
            $table->dropColumn([
                'card_reader_seconds',
                'card_reader_checkout_time_1',
                'card_reader_checkout_time_2',
                'heating_start_time',
                'heating_end_time',
                'payment_buffer_seconds',
                'card_reader_no',
                'key_no',
                'invoice_status',
                'welcome_gift_enabled',
                'is_spring_slot_1_10',
                'is_spring_slot_11_20',
                'is_spring_slot_21_30',
                'is_spring_slot_31_40',
                'is_spring_slot_41_50',
                'is_spring_slot_51_60',
                'member_system_enabled',
                'payment_config_id',
                'machine_model_id',
                'creator_id',
                'updater_id',
            ]);
        });
    }
};
