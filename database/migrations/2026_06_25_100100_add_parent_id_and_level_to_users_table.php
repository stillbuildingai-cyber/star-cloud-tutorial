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
     * 帳號樹狀層級化：加入 parent_id（建立者）與 level（主帳號=0）。
     * 並對既有資料做兩步 backfill：
     *   1. is_admin 正規化：每家「有帳號但無主帳號」的公司，將最早建立的帳號設為主帳號；多主帳號則只保留最早一個。
     *   2. 回填 parent_id / level：主帳號 level=0、parent_id=null；其餘帳號掛在該公司主帳號下、level=1。
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('company_id');
            $table->unsignedTinyInteger('level')->default(0)->after('parent_id');
            $table->foreign('parent_id')->references('id')->on('users')->nullOnDelete();
            $table->index('parent_id');
        });

        $companyIds = DB::table('companies')->pluck('id');

        // 1. is_admin 正規化
        foreach ($companyIds as $cid) {
            // a. 軟刪除帳號一律不得為主帳號
            DB::table('users')
                ->where('company_id', $cid)
                ->whereNotNull('deleted_at')
                ->where('is_admin', true)
                ->update(['is_admin' => false]);

            // b. 在「未軟刪除」帳號中確保恰有一個主帳號
            $admins = DB::table('users')
                ->where('company_id', $cid)
                ->whereNull('deleted_at')
                ->where('is_admin', true)
                ->orderBy('id')
                ->pluck('id');

            if ($admins->isEmpty()) {
                // 無主帳號 → 將最早建立的帳號補為主帳號
                $firstId = DB::table('users')
                    ->where('company_id', $cid)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->value('id');
                if ($firstId) {
                    DB::table('users')->where('id', $firstId)->update(['is_admin' => true]);
                }
            } elseif ($admins->count() > 1) {
                // 多主帳號 → 只保留最早一個，其餘降為一般帳號
                DB::table('users')
                    ->whereIn('id', $admins->slice(1)->values())
                    ->update(['is_admin' => false]);
            }
        }

        // 2. 回填 parent_id / level
        foreach ($companyIds as $cid) {
            // 主帳號必須取自「未軟刪除」帳號
            $mainId = DB::table('users')
                ->where('company_id', $cid)
                ->whereNull('deleted_at')
                ->where('is_admin', true)
                ->value('id');

            if (!$mainId) {
                continue; // 空公司，略過
            }

            // 主帳號
            DB::table('users')->where('id', $mainId)->update(['parent_id' => null, 'level' => 0]);
            // 其餘帳號（含軟刪除）掛在主帳號下，level=1
            DB::table('users')
                ->where('company_id', $cid)
                ->where('id', '!=', $mainId)
                ->update(['parent_id' => $mainId, 'level' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 防禦式：外鍵 / 索引 / 欄位可能因 DDL 半途失敗而部分不存在，逐項忽略不存在的情況。
        try {
            Schema::table('users', fn (Blueprint $t) => $t->dropForeign(['parent_id']));
        } catch (\Throwable $e) {
            // 外鍵已不存在，略過
        }
        try {
            Schema::table('users', fn (Blueprint $t) => $t->dropIndex('users_parent_id_index'));
        } catch (\Throwable $e) {
            // 索引已不存在，略過
        }
        Schema::table('users', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['parent_id', 'level'],
                fn ($c) => Schema::hasColumn('users', $c)
            ));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
