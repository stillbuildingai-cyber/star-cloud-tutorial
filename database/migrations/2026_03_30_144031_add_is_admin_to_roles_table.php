<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('is_system');
        });

        // 資料遷移：將所有租戶中名稱為「管理員」的角色標示為 is_admin = true
        // 這樣既有的授權篩選才不會斷掉
        DB::table('roles')
            ->whereNotNull('company_id')
            ->where('name', '管理員')
            ->update(['is_admin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
