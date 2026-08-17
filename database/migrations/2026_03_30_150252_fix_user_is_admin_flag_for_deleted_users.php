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
        // 1. 先將所有已刪除帳號的 is_admin 全部歸零，確保不會標記在「看不到的人」身上
        DB::table('users')->whereNotNull('deleted_at')->update(['is_admin' => false]);

        // 2. 針對每一家公司，重新撈取「目前還存活 (deleted_at is null)」的最早建立帳號
        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            // 找該公司中，目前 ID 最小且「尚未被刪除」的 User
            $userId = DB::table('users')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->orderBy('id', 'asc')
                ->value('id');

            if ($userId) {
                // 將該帳號設為管理員，並確保該公司其它生存帳號如果是 true 的先清掉 (一對一標記)
                DB::table('users')
                    ->where('company_id', $companyId)
                    ->where('id', '!=', $userId)
                    ->update(['is_admin' => false]);

                DB::table('users')->where('id', $userId)->update(['is_admin' => true]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 基本上這是資料修正，回復也不太有意義
    }
};
