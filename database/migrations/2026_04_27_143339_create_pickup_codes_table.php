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
        Schema::create('pickup_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('slot_no')->comment('貨道編號');
            $table->string('code', 10)->comment('取貨碼 (8 位)');
            $table->timestamp('expires_at')->comment('到期時間');
            $table->timestamp('used_at')->nullable()->comment('使用時間');
            $table->string('status')->default('active')->comment('狀態: active, used, expired, cancelled');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['company_id', 'machine_id', 'code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_codes');
    }
};
