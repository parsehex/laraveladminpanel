<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    public function test_guest_is_redirected_to_login_from_roles_index(): void
    {
        $response = $this->get(route('admin.roles.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_user_without_roles_view_cannot_open_roles_index(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->active()->create(['role' => 'technician']);
        $user->syncRoles(['technician']);

        $this->actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_sees_roles_and_permission_modules_on_one_page(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.roles.index'));

        $response->assertOk();
        $response->assertViewIs('admin.roles.index');
        $response->assertSee('New role');
        $response->assertSee('admin');
        $response->assertSee('technician');
        $response->assertSee('Inventory');
        $response->assertDontSee('Back to roles');
    }

    public function test_index_escapes_role_name_html(): void
    {
        $user = $this->adminUser();

        Role::query()->create([
            'name' => '<script>alert(1)</script>',
            'guard_name' => 'web',
            'description' => '<b>bold</b>',
        ]);

        $response = $this->actingAs($user)->get(route('admin.roles.index'));

        $response->assertSee('<script>alert(1)</script>');
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('<b>bold</b>', false);
    }

    public function test_create_and_edit_pages_redirect_to_the_roles_index(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->where('name', 'technician')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.roles.create'))
            ->assertRedirectToRoute('admin.roles.index');

        $this->actingAs($user)
            ->get(route('admin.roles.edit', $role))
            ->assertRedirectToRoute('admin.roles.index');
    }

    public function test_authorized_user_can_create_a_role_with_selected_permissions(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.roles.store'), [
            'name' => 'warehouse',
            'guard_name' => 'web',
            'description' => 'Warehouse staff',
            'permissions' => ['inventory.view', 'parts.view'],
        ]);

        $response->assertRedirectToRoute('admin.roles.index');
        $response->assertSessionHas('success');

        $role = Role::query()->where('name', 'warehouse')->firstOrFail();

        $this->assertSame('Warehouse staff', $role->description);
        $this->assertSame(
            ['inventory.view', 'parts.view'],
            $role->permissions->pluck('name')->sort()->values()->all()
        );
    }

    public function test_store_rejects_a_missing_role_name(): void
    {
        $user = $this->adminUser();
        $roleCount = Role::query()->count();

        $response = $this->actingAs($user)
            ->from(route('admin.roles.index'))
            ->post(route('admin.roles.store'), [
                'name' => '',
                'guard_name' => 'web',
            ]);

        $response->assertRedirectToRoute('admin.roles.index');
        $response->assertSessionHasErrors(['name' => 'The name field is required.']);
        $this->assertSame($roleCount, Role::query()->count());
    }

    public function test_store_rejects_a_duplicate_role_name(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->from(route('admin.roles.index'))
            ->post(route('admin.roles.store'), [
                'name' => 'admin',
                'guard_name' => 'web',
            ]);

        $response->assertRedirectToRoute('admin.roles.index');
        $response->assertSessionHasErrors(['name' => 'The name has already been taken.']);
    }

    public function test_user_without_roles_create_cannot_store_a_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->active()->create(['role' => 'technician']);
        $user->syncRoles(['technician']);

        $this->actingAs($user)
            ->post(route('admin.roles.store'), [
                'name' => 'warehouse',
                'guard_name' => 'web',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'warehouse']);
    }

    public function test_authorized_user_can_update_role_permissions(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->where('name', 'kit_maker')->firstOrFail();

        $response = $this->actingAs($user)->put(route('admin.roles.update', $role), [
            'name' => 'kit_maker',
            'guard_name' => 'web',
            'description' => 'Updated description',
            'permissions' => ['inventory.view', 'kits.build', 'kits.view'],
        ]);

        $response->assertRedirectToRoute('admin.roles.index');
        $response->assertSessionHas('success');

        $role->refresh();

        $this->assertSame('Updated description', $role->description);
        $this->assertSame(
            ['inventory.view', 'kits.build', 'kits.view'],
            $role->permissions->pluck('name')->sort()->values()->all()
        );
    }

    public function test_protected_role_cannot_be_renamed(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->where('name', 'admin')->firstOrFail();

        $response = $this->actingAs($user)
            ->from(route('admin.roles.index'))
            ->put(route('admin.roles.update', $role), [
                'name' => 'not-admin',
                'guard_name' => 'web',
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name')->all(),
            ]);

        $response->assertRedirectToRoute('admin.roles.index');
        $response->assertSessionHas('error');
        $this->assertSame('admin', $role->fresh()->name);
    }

    public function test_protected_role_cannot_be_deleted(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->where('name', 'admin')->firstOrFail();

        $response = $this->actingAs($user)->delete(route('admin.roles.destroy', $role));

        $response->assertRedirectToRoute('admin.roles.index');
        $response->assertSessionHas('error');
        $this->assertModelExists($role);
    }

    public function test_custom_role_without_users_can_be_deleted(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->create([
            'name' => 'temp-role',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($user)->delete(route('admin.roles.destroy', $role));

        $response->assertRedirectToRoute('admin.roles.index');
        $response->assertSessionHas('success');
        $this->assertModelMissing($role);
    }

    public function test_role_assigned_to_users_cannot_be_deleted(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->create([
            'name' => 'assigned-role',
            'guard_name' => 'web',
        ]);
        $assignee = User::factory()->active()->create(['role' => 'user']);
        $assignee->syncRoles([$role]);

        $response = $this->actingAs($user)->delete(route('admin.roles.destroy', $role));

        $response->assertRedirectToRoute('admin.roles.index');
        $response->assertSessionHas('error');
        $this->assertModelExists($role);
    }
}
