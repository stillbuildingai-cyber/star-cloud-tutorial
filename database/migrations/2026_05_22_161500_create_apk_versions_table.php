<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('apk_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version_name'); // 例如 '10_08_6_R'
            $table->integer('version_code')->unique(); // 例如 100806，用於比對版本新舊，必須遞增
            $table->string('flavor'); // 機台風味，例如 'S_12_XY_U_Standard_'
            $table->string('file_path')->nullable(); // 本地上傳的 APK 儲存路徑
            $table->string('download_url')->nullable(); // 外連下載 URL (如果未使用本地上傳)
            $table->text('release_notes')->nullable(); // 版本更新說明
            $table->boolean('is_forced')->default(false); // 是否強制更新
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apk_versions');
    }
};
