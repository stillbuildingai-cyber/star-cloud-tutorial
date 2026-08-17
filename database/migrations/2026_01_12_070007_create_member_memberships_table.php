<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 會員等級紀錄
     */
    public function up(): void
    {
        Schema::create('member_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('tier_id')->constrained('membership_tiers')->onDelete('cascade');
            $table->datetime('starts_at')->comment('生效日');
            $table->datetime('expires_at')->nullable()->comment('到期日');
            $table->unsignedBigInteger('payment_id')->nullable()->comment('付款紀錄ID');
            $table->boolean('auto_renew')->default(false)->comment('是否自動續約');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_memberships');
    }
};
