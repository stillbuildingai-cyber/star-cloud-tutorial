<?php

namespace Tests\Feature;

use App\Models\System\Company;
use App\Models\System\User;
use App\Models\System\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_cannot_delete_role_with_active_users()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'status' => 1,
        ]);
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin_user_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);
        $admin->assignRole('super-admin');

        $role = Role::create([
            'name' => 'Test Role',
            'company_id' => $company->id,
            'guard_name' => 'web',
            'is_system' => false,
        ]);

        $user = User::create([
            'name' => 'Test User',
            'username' => 'test_user_' . uniqid(),
            'email' => 'user_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($admin)
            ->delete(route('admin.permission.roles.destroy', $role->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', __('Cannot delete role with active users.'));
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_can_delete_role_without_active_users()
    {
        $company = Company::create([
            'name' => 'Test Company 2',
            'code' => 'TEST2',
            'status' => 1,
        ]);
        $admin = User::create([
            'name' => 'Admin User 2',
            'username' => 'admin_user_2_' . uniqid(),
            'email' => 'admin2_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);
        $admin->assignRole('super-admin');

        $role = Role::create([
            'name' => 'Empty Role',
            'company_id' => $company->id,
            'guard_name' => 'web',
            'is_system' => false,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.permission.roles.destroy', $role->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }
}
