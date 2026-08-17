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
        // 1. 取貨碼表增加 order_id 欄位
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->after('created_by')->nullable()->index()->comment('關聯訂單ID');
        });

        // 2. 建立通行碼專用的使用紀錄表 (各自的紀錄)
        Schema::create('pass_code_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->foreignId('pass_code_id')->constrained('pass_codes')->onDelete('cascade');
            $table->unsignedBigInteger('order_id')->nullable()->index()->comment('關聯訂單ID');
            $table->string('status')->default('verified')->comment('狀態: verified, completed');
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'machine_id', 'pass_code_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pass_code_logs');
        Schema::table('pickup_codes', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });
    }
};
