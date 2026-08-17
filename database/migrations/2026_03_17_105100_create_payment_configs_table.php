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
        Schema::create('payment_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade')->comment('關聯公司');
            $table->string('name')->comment('組合名稱');
            $table->json('settings')->nullable()->comment('金流參數 (JSON)');
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete()->comment('建立者');
            $table->foreignId('updater_id')->nullable()->constrained('users')->nullOnDelete()->comment('修改者');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_configs');
    }
};
