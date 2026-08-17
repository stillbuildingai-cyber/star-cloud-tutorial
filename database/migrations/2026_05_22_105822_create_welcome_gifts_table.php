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
        Schema::create('welcome_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('name', 100)->comment('來店禮名稱');
            $table->string('code', 10)->comment('來店禮代碼 (8位)');
            $table->string('slug', 16)->unique()->comment('票券 Slug');
            $table->string('discount_type')->comment('折扣類型: percentage (趴數), amount (金額)');
            $table->integer('discount_value')->comment('折扣值 (趴數或折抵金額)');
            $table->string('usage_type')->default('once')->comment('使用次數: once (單次), unlimited (無限制)');
            $table->integer('usage_limit')->nullable()->default(1)->comment('使用次數上限');
            $table->integer('usage_count')->default(0)->comment('已使用次數');
            $table->timestamp('expires_at')->nullable()->comment('到期時間');
            $table->string('status')->default('active')->comment('狀態: active, disabled, used, expired');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['company_id', 'machine_id', 'code', 'status']);
        });

        Schema::create('welcome_gift_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->foreignId('welcome_gift_id')->constrained('welcome_gifts')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('action')->comment('動作: verify_success, consume, cancel');
            $table->string('remark')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('welcome_gift_logs');
        Schema::dropIfExists('welcome_gifts');
    }
};
