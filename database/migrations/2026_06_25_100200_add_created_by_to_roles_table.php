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
     * 角色樹狀層級化：加入 created_by（建立者帳號）。
     * Backfill：既有公司層級角色 created_by 指向該公司主帳號；全域系統角色維持 null。
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('company_id');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('created_by');
        });

        // 既有公司角色歸屬該公司主帳號（未軟刪除的 is_admin）
        $companyIds = DB::table('roles')->whereNotNull('company_id')->distinct()->pluck('company_id');
        foreach ($companyIds as $cid) {
            $mainId = DB::table('users')
                ->where('company_id', $cid)
                ->whereNull('deleted_at')
                ->where('is_admin', true)
                ->value('id');
            if ($mainId) {
                DB::table('roles')
                    ->where('company_id', $cid)
                    ->whereNull('created_by')
                    ->update(['created_by' => $mainId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('roles', fn (Blueprint $t) => $t->dropForeign(['created_by']));
        } catch (\Throwable $e) {
            // 外鍵已不存在，略過
        }
        try {
            Schema::table('roles', fn (Blueprint $t) => $t->dropIndex('roles_created_by_index'));
        } catch (\Throwable $e) {
            // 索引已不存在，略過
        }
        if (Schema::hasColumn('roles', 'created_by')) {
            Schema::table('roles', fn (Blueprint $t) => $t->dropColumn('created_by'));
        }
    }
};
