<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 點數規則
     */
    public function up(): void
    {
        Schema::create('point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('規則名稱');
            $table->enum('trigger', ['purchase', 'deposit', 'register', 'birthday', 'referral'])->comment('觸發條件');
            $table->integer('points_per_unit')->default(1)->comment('每單位獲得點數');
            $table->decimal('unit_amount', 12, 2)->default(100)->comment('單位金額');
            $table->integer('validity_days')->default(365)->comment('點數有效天數');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_rules');
    }
};
