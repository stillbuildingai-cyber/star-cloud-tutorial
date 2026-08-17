<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 會員錢包
     */
    public function up(): void
    {
        Schema::create('member_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0)->comment('錢包餘額');
            $table->decimal('bonus_balance', 12, 2)->default(0)->comment('回饋金餘額');
            $table->timestamps();

            $table->unique('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_wallets');
    }
};
