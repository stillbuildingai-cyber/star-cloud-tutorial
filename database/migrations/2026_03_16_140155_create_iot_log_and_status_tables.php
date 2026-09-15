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
        Schema::create('remote_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('command_type', 50)->comment('reboot, lock, stock_update, dispense...');
            $table->enum('status', ['pending', 'sent', 'success', 'failed'])->default('pending');
            $table->json('payload')->nullable()->comment('指令參數');
            $table->integer('ttl')->default(60)->comment('失效秒數');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['machine_id', 'status']);
        });

        // 註：原本這裡還會建立 coin_inventories (投幣機零錢統計) 與 timer_statuses
        // (計時器/貨道商品狀態，且外鍵指向已移除的 products 表) 兩張表，兩者皆屬於
        // 已整支移除的販賣機投幣/商品模組，教學版智慧插座不需要，予以移除。
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_commands');
    }
};
