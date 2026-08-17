<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 錢包交易紀錄
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->enum('type', ['deposit', 'consume', 'refund', 'bonus', 'adjust'])->comment('交易類型');
            $table->decimal('amount', 12, 2)->comment('異動金額');
            $table->decimal('balance_after', 12, 2)->comment('異動後餘額');
            $table->string('description')->nullable()->comment('說明');
            $table->string('reference_type')->nullable()->comment('關聯類型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('關聯ID');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['member_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
