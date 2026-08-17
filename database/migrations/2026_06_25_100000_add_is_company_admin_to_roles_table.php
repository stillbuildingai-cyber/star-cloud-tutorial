<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 為公司層級的「管理員」角色加入明確的權威旗標 is_company_admin，
     * 取代過去靠中文 name='管理員' 來辨識主帳號角色的脆弱作法。
     */
    public function up(): void
    {
        if (!Schema::hasColumn('roles', 'is_company_admin')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_company_admin')->default(false)->after('is_system');
            });
        }

        // Backfill：既有的公司層級「管理員」角色標記為 is_company_admin = true
        DB::table('roles')
            ->whereNotNull('company_id')
            ->where('name', '管理員')
            ->update(['is_company_admin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('roles', 'is_company_admin')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('is_company_admin');
            });
        }
    }
};
