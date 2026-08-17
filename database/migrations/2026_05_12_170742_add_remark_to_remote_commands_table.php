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
        Schema::table('remote_commands', function (Blueprint $table) {
            $table->string('remark')->nullable()->after('note')->comment('操作備註');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remote_commands', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};
