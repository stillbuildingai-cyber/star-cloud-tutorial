<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 建立倉庫主表
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->comment('所屬租戶');
            $table->string('name')->comment('倉庫名稱');
            $table->enum('type', ['main', 'branch'])->default('branch')->comment('倉庫類型：總倉/分倉');
            $table->string('address')->nullable()->comment('地址');
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('負責人');
            $table->boolean('is_active')->default(true)->comment('啟用/停用');
            $table->timestamps();
            $table->softDeletes();

            // 索引：常用篩選條件
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
