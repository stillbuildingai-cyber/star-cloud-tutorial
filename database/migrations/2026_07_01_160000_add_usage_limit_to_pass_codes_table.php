<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pass_codes', function (Blueprint $table) {
            // 可用次數上限；null=無限次(維持原行為)。勾「只能使用一次」時=1。
            $table->unsignedInteger('usage_limit')->nullable()->after('status');
            $table->unsignedInteger('usage_count')->default(0)->after('usage_limit');
        });
    }

    public function down(): void
    {
        Schema::table('pass_codes', function (Blueprint $table) {
            $table->dropColumn(['usage_limit', 'usage_count']);
        });
    }
};
