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
        // 1. 取得權限 ID
        $permission = DB::table('permissions')
            ->where('name', 'menu.data-config.sub-account-roles')
            ->first();

        if ($permission) {
            // 2. 移除角色與該權限的關聯 (雖然 Spatie 通常會處理，但手動確保清理乾淨)
            DB::table('role_has_permissions')
                ->where('permission_id', $permission->id)
                ->delete();

            // 3. 移除權限本身
            DB::table('permissions')
                ->where('id', $permission->id)
                ->delete();
        }

        // 4. 清理權限快取 (如果有的話)
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Exception $e) {
            // 忽略快取清理失敗（例如在沒有 Redis 的環境中）
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 由於是要永久拿掉，down 邏輯通常不需要重建，
        // 若真要復原，應透過重跑 Seeder 或手動新增。
    }
};
