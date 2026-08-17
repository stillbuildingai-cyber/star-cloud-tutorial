<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ota_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apk_version_id')->constrained('apk_versions')->onDelete('cascade');
            $table->dateTime('scheduled_at');
            $table->string('target_type')->default('custom'); // 'all', 'custom'
            $table->json('target_value')->nullable(); // 當 target_type = 'custom' 時，儲存 [1, 2, 3] 機台 ID 的 JSON 陣列
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed'
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // 建立排程的使用者
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ota_schedules');
    }
};
