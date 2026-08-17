<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 會員禮品發放紀錄
     */
    public function up(): void
    {
        Schema::create('member_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('gift_definition_id')->constrained('gift_definitions')->onDelete('cascade');
            $table->enum('status', ['pending', 'claimed', 'expired'])->default('pending');
            $table->datetime('claimed_at')->nullable()->comment('領取時間');
            $table->datetime('expires_at')->nullable()->comment('有效期限');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['member_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_gifts');
    }
};
