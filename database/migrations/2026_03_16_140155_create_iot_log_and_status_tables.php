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

        Schema::create('coin_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->integer('value_1')->default(0);
            $table->integer('value_5')->default(0);
            $table->integer('value_10')->default(0);
            $table->integer('value_50')->default(0);
            $table->integer('value_100')->default(0);
            $table->integer('value_500')->default(0);
            $table->integer('value_1000')->default(0);
            $table->string('operator')->nullable()->comment('操作人 (0=消費者)');
            $table->timestamps();
        });

        Schema::create('timer_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('slot_no')->comment('貨道 ID (B710 cid)');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->tinyInteger('status')->default(0)->comment('0:未啟用/1:使用中/2:異常');
            $table->integer('remaining_seconds')->default(0);
            $table->timestamps();

            $table->unique(['machine_id', 'slot_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timer_statuses');
        Schema::dropIfExists('coin_inventories');
        Schema::dropIfExists('remote_commands');
    }
};
