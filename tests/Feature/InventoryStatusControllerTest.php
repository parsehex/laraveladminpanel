<?php

namespace Tests\Feature;

use App\Models\InventoryStatus;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\InventoryStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function technicianUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryStatusSeeder::class);

        $user = User::factory()->active()->create(['role' => 'technician']);
        $user->syncRoles(['technician']);

        return $user;
    }

    private function createTruck(User $user): Truck
    {
        return Truck::query()->create([
            'name' => 'Status Truck',
            'units_on_truck' => 1,
            'cost_of_truck' => 500,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_from_statuses_index(): void
    {
        $response = $this->get(route('admin.inventory-statuses.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_technician_cannot_open_statuses_index(): void
    {
        $user = $this->technicianUser();

        $this->actingAs($user)
            ->get(route('admin.inventory-statuses.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_sees_seeded_statuses(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.inventory-statuses.index'));

        $response->assertOk();
        $response->assertViewIs('admin.inventory-statuses.index');
        $response->assertSee('Triage');
        $response->assertSee('Show Room');
        $response->assertSee('Showroom');
        $response->assertSee('System');
    }

    public function test_authorized_user_can_create_a_custom_status(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.inventory-statuses.store'), [
            'name' => 'Awaiting Pickup',
            'auto_location' => 'Loading Dock',
        ]);

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('inventory_statuses', [
            'name' => 'Awaiting Pickup',
            'auto_location' => 'Loading Dock',
            'is_system' => false,
        ]);
    }

    public function test_duplicate_status_name_is_rejected(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)
            ->from(route('admin.inventory-statuses.index'))
            ->post(route('admin.inventory-statuses.store'), [
                'name' => 'Ready',
            ]);

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $response->assertInvalid(['name' => 'already been taken']);
    }

    public function test_authorized_user_can_update_auto_location(): void
    {
        $user = $this->adminUser();
        $status = InventoryStatus::query()->where('name', 'Show Room')->firstOrFail();

        $response = $this->actingAs($user)->patch(route('admin.inventory-statuses.update', $status), [
            'auto_location' => 'Front Floor',
        ]);

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $this->assertDatabaseHas('inventory_statuses', [
            'id' => $status->id,
            'name' => 'Show Room',
            'auto_location' => 'Front Floor',
        ]);
    }

    public function test_system_status_cannot_be_archived(): void
    {
        $user = $this->adminUser();
        $status = InventoryStatus::query()->where('name', 'Testing')->firstOrFail();

        $response = $this->actingAs($user)
            ->from(route('admin.inventory-statuses.index'))
            ->post(route('admin.inventory-statuses.archive', $status));

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $response->assertInvalid(['status' => 'System statuses cannot be archived.']);
        $this->assertNull($status->fresh()->archived_at);
    }

    public function test_status_in_use_cannot_be_archived(): void
    {
        $user = $this->adminUser();
        $status = InventoryStatus::factory()->create(['name' => 'Awaiting Pickup']);
        $truck = $this->createTruck($user);
        TruckAppliance::query()->create([
            'truck_id' => $truck->id,
            'serial_number' => 'IN-USE-1',
            'status' => 'Awaiting Pickup',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('admin.inventory-statuses.index'))
            ->post(route('admin.inventory-statuses.archive', $status));

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $response->assertInvalid(['status' => 'cannot be archived while items use it']);
        $this->assertNull($status->fresh()->archived_at);
    }

    public function test_unused_custom_status_can_be_archived(): void
    {
        $user = $this->adminUser();
        $status = InventoryStatus::factory()->create(['name' => 'Awaiting Pickup']);

        $response = $this->actingAs($user)->post(route('admin.inventory-statuses.archive', $status));

        $response->assertRedirectToRoute('admin.inventory-statuses.index');
        $response->assertSessionHas('success');
        $this->assertNotNull($status->fresh()->archived_at);
    }

    public function test_index_escapes_status_name_html(): void
    {
        $user = $this->adminUser();
        InventoryStatus::factory()->create([
            'name' => '<script>alert(1)</script>',
            'auto_location' => '<b>bold</b>',
        ]);

        $response = $this->actingAs($user)->get(route('admin.inventory-statuses.index'));

        $response->assertSee('<script>alert(1)</script>');
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('<b>bold</b>', false);
    }
}
