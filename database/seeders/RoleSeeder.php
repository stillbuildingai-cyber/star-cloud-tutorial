<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\System\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 重設快取
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 建立權限
        $permissions = [
            'menu.members',
            'menu.machines',
            'menu.machines.list',
            'menu.machines.permissions',
            'menu.machines.utilization',
            'menu.machines.maintenance',
            'menu.app',
            'menu.analysis',
            'menu.analysis.machine-reports',
            'menu.audit',
            'menu.data-config',
            'menu.data-config.sub-accounts',
            'menu.data-config.points',
            'menu.remote',
            'menu.remote.commands',
            'menu.line',
            'menu.reservation',
            'menu.special-permission',
            'menu.special-permission.clear-stock',
            'menu.basic',
            'menu.basic.machines',
            'menu.permissions',
            'menu.permissions.companies',
            'menu.permissions.accounts',
            'menu.permissions.roles',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 建立角色
        $superAdmin = Role::updateOrCreate(
            ['name' => 'super-admin', 'company_id' => null],
            ['is_system' => true, 'guard_name' => 'web']
        );
        // 教學版只聚焦智慧插座（機台管理／指令中心／機台設定等），
        // 販賣機專屬功能（倉庫、銷售、商品、金流等）已從程式碼整支移除，
        // 這裡自然也不再定義/授權對應權限，跟正式站 iot.stillbuilding.life 目前的精簡設定一致。
        $superAdmin->syncPermissions([
            'menu.machines',
            'menu.machines.list',
            'menu.machines.permissions',
            'menu.machines.utilization',
            'menu.machines.maintenance',
            'menu.analysis',
            'menu.analysis.machine-reports',
            'menu.data-config',
            'menu.data-config.sub-accounts',
            'menu.remote',
            'menu.remote.commands',
            'menu.basic',
            'menu.basic.machines',
            'menu.permissions',
            'menu.permissions.companies',
            'menu.permissions.accounts',
            'menu.permissions.roles',
        ]);

        $tenantAdmin = Role::updateOrCreate(
            ['name' => '客戶管理員角色模板', 'company_id' => null],
            ['is_system' => true, 'guard_name' => 'web']
        );
        $tenantAdmin->syncPermissions([
            'menu.members',
            'menu.machines',
            'menu.machines.list',
            'menu.machines.permissions',
            'menu.machines.utilization',
            'menu.machines.maintenance',
            'menu.app',
            'menu.analysis',
            'menu.analysis.machine-reports',
            'menu.audit',
            'menu.data-config',
            'menu.data-config.sub-accounts',
            'menu.data-config.points',
            'menu.remote',
            'menu.remote.commands',
            'menu.line',
            'menu.reservation',
            'menu.special-permission',
            'menu.special-permission.clear-stock',
        ]);
    }
}