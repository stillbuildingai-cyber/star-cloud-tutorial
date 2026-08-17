<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 點數異動紀錄
     */
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->enum('type', ['earn', 'use', 'expire', 'gift', 'adjust'])->comment('異動類型');
            $table->integer('points')->comment('異動點數');
            $table->integer('balance_after')->comment('異動後餘額');
            $table->string('description')->nullable()->comment('說明');
            $table->datetime('expires_at')->nullable()->comment('此筆點數到期日');
            $table->string('reference_type')->nullable()->comment('關聯類型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('關聯ID');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['member_id', 'created_at']);
            $table->index('expires_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
