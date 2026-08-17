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
        Schema::create('staff_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index()->comment('租戶ID');
            $table->string('employee_id')->index()->comment('員工編號');
            $table->string('name')->comment('員工姓名');
            $table->string('card_uid')->index()->comment('IC卡物理卡號');
            $table->string('status')->default('active')->comment('狀態: active/inactive');
            $table->text('notes')->nullable()->comment('備註');
            $table->timestamp('last_used_at')->nullable()->comment('最後使用時間');
            $table->unsignedBigInteger('last_machine_id')->nullable()->comment('最後使用機台');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_cards');
    }
};
