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
        Schema::create('machine_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('slot_no')->comment('貨道編號 (B017 tid, B710 cid)');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->integer('stock')->default(0)->comment('當前庫存');
            $table->integer('max_stock')->default(0)->comment('最大容量');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['machine_id', 'slot_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_slots');
    }
};
