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
        // 1. 從 roles 移除 is_admin
        if (Schema::hasColumn('roles', 'is_admin')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }

        // 2. 在 users 新增 is_admin
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('status');
        });

        // 3. 資料遷移：針對現有租戶，將每一家公司最先建立的帳號（或是目前名稱為管理員角色的人）標記為 is_admin = true
        // 取得所有租戶公司 ID
        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            // 優先找該公司 ID 最小的 user (通常是第一個建立的)
            $userId = DB::table('users')
                ->where('company_id', $companyId)
                ->orderBy('id', 'asc')
                ->value('id');

            if ($userId) {
                DB::table('users')->where('id', $userId)->update(['is_admin' => true]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('is_system');
        });
    }
};
