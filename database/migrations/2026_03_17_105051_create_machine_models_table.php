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
        Schema::create('machine_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('型號名稱');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade')->comment('關聯公司');
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
        Schema::dropIfExists('machine_models');
    }
};
