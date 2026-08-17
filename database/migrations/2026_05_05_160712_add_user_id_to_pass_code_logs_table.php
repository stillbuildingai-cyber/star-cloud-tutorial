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
        Schema::table('pass_code_logs', function (Blueprint $table) {
            $table->string('action')->after('pass_code_id')->nullable()->index()->comment('動作類型');
            $table->unsignedBigInteger('user_id')->nullable()->after('action')->index()->comment('操作人員ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pass_code_logs', function (Blueprint $table) {
            $table->dropColumn(['action', 'user_id']);
        });
    }
};
