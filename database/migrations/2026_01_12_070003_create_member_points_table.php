<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 會員點數帳戶
     */
    public function up(): void
    {
        Schema::create('member_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->integer('available_points')->default(0)->comment('可用點數');
            $table->integer('pending_points')->default(0)->comment('待生效點數');
            $table->integer('expired_points')->default(0)->comment('已過期點數(統計)');
            $table->integer('used_points')->default(0)->comment('已使用點數(統計)');
            $table->timestamps();

            $table->unique('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_points');
    }
};
