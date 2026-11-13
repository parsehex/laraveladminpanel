<?php

namespace Tests\Feature;

use App\Models\ItemCheckIn;
use App\Models\Model as ApplianceModel;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('admin.inventory.check-in.create'))
            ->assertRedirect(route('login'));
    }

    public function test_serial_match_checks_in_the_units_truck_without_a_truck_being_submitted(): void
    {
        $this->freezeTime();
        $admin = $this->userWithRole('admin');
        $truck = $this->createTruck($admin, 'WF-003');
        $model = $this->createModel($admin, 'W123');
        $appliance = $this->createAppliance($truck, $admin, [
            'unit_label' => 'WF-003-001',
            'serial_number' => '00123',
            'brand' => 'Ashley',
            'product_name' => 'Sofa',
            'status' => 'Testing',
            'model_id' => null,
        ]);

        $this->fakeLabel([
            'brand' => 'OtherBrand',
            'model_number' => 'w-123',
            'product_name' => 'Other Name',
            'serial_number' => '123',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.store'), [
                'photos' => [UploadedFile::fake()->image('label.jpg')],
            ])
            ->assertRedirect();

        $checkIn = ItemCheckIn::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.inventory.check-in.show', $checkIn))
            ->assertOk()
            ->assertSee('WF-003')
            ->assertSee('WF-003-001');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
            && $request['model'] === 'anthropic/claude-sonnet-4.5');

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.confirm', $checkIn), [
                'intent' => 'match',
                'appliance_id' => $appliance->id,
                'brand' => 'OtherBrand',
                'model_number' => 'W123',
                'product_name' => 'Other Name',
                'serial_number' => '123',
            ])
            ->assertRedirect(route('admin.inventory.show', $appliance))
            ->assertSessionHas('success');

        $appliance->refresh();
        $this->assertSame('Testing', $appliance->status);
        $this->assertSame('00123', $appliance->serial_number);
        $this->assertSame('Ashley', $appliance->brand);
        $this->assertSame('Sofa', $appliance->product_name);
        $this->assertSame($model->id, $appliance->model_id);
        $this->assertSame($truck->id, $appliance->truck_id);
        $this->assertNotNull($appliance->checked_in_at);
        $this->assertSame($admin->id, $appliance->checked_in_by);
        $this->assertCount(1, $appliance->photos);
        $stampedAt = $appliance->checked_in_at;

        $this->travel(5)->minutes();

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.store'), [
                'photos' => [UploadedFile::fake()->image('again.jpg')],
            ])
            ->assertRedirect();

        $second = ItemCheckIn::query()->latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.confirm', $second), [
                'intent' => 'match',
                'appliance_id' => $appliance->id,
                'brand' => 'OtherBrand',
                'model_number' => 'W123',
                'product_name' => 'Other Name',
                'serial_number' => '123',
            ])
            ->assertRedirect(route('admin.inventory.show', $appliance));

        $appliance->refresh();
        $this->assertTrue($appliance->checked_in_at->eq($stampedAt));
        $this->assertCount(2, $appliance->photos);
        $this->assertSame('00123', $appliance->serial_number);
        $this->assertSame('Testing', $appliance->status);
    }

    public function test_model_match_lists_open_units_and_their_trucks(): void
    {
        $admin = $this->userWithRole('admin');
        $model = $this->createModel($admin, 'B123');
        $alpha = $this->createTruck($admin, 'Alpha Yard');
        $beta = $this->createTruck($admin, 'Beta Yard');
        $soldTruck = $this->createTruck($admin, 'Sold Yard');
        $doneTruck = $this->createTruck($admin, 'Done Yard');
        $this->createAppliance($alpha, $admin, ['unit_label' => 'Alpha-001', 'model_id' => $model->id, 'status' => 'Testing', 'serial_number' => null]);
        $this->createAppliance($beta, $admin, ['unit_label' => 'Beta-001', 'model_id' => $model->id, 'status' => 'Testing', 'serial_number' => null]);
        $this->createAppliance($soldTruck, $admin, ['unit_label' => 'Sold-001', 'model_id' => $model->id, 'status' => 'Sold', 'serial_number' => null]);
        $this->createAppliance($doneTruck, $admin, [
            'unit_label' => 'Done-001',
            'model_id' => $model->id,
            'status' => 'Testing',
            'serial_number' => null,
            'checked_in_at' => now(),
            'checked_in_by' => $admin->id,
        ]);

        $this->fakeLabel([
            'brand' => 'Ashley',
            'model_number' => 'b123',
            'product_name' => 'Chair',
            'serial_number' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.store'), [
                'photos' => [UploadedFile::fake()->image('label.jpg')],
            ])
            ->assertRedirect();

        $checkIn = ItemCheckIn::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.inventory.check-in.show', $checkIn))
            ->assertOk()
            ->assertSee('Open units of this model')
            ->assertSee('Alpha Yard')
            ->assertSee('Alpha-001')
            ->assertSee('Beta Yard')
            ->assertSee('Beta-001')
            ->assertDontSee('Sold-001')
            ->assertDontSee('Done-001');
    }

    public function test_add_requires_a_truck_and_creates_the_unit_on_that_truck(): void
    {
        $admin = $this->userWithRole('admin');
        $truck = $this->createTruck($admin, 'Arrival');

        $this->fakeLabel([
            'brand' => 'Ashley',
            'model_number' => 'new-1',
            'product_name' => 'Chair',
            'serial_number' => '9988',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.store'), [
                'photos' => [UploadedFile::fake()->image('label.jpg')],
            ])
            ->assertRedirect();

        $checkIn = ItemCheckIn::query()->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.inventory.check-in.show', $checkIn))
            ->post(route('admin.inventory.check-in.confirm', $checkIn), [
                'intent' => 'add',
                'brand' => 'Ashley',
                'model_number' => 'NEW-1',
                'product_name' => 'Chair',
                'serial_number' => '9988',
                'category' => 'Sofas',
                'msrp' => '0',
            ])
            ->assertRedirect(route('admin.inventory.check-in.show', $checkIn))
            ->assertSessionHasErrors('truck_id');

        $this->assertNull($checkIn->fresh()->confirmed_at);
        $this->assertSame(0, TruckAppliance::query()->count());

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.confirm', $checkIn), [
                'intent' => 'add',
                'truck_id' => $truck->id,
                'brand' => 'Ashley',
                'model_number' => 'NEW-1',
                'product_name' => 'Chair',
                'serial_number' => '9988',
                'category' => 'Sofas',
                'msrp' => '0',
            ])
            ->assertRedirect();

        $appliance = TruckAppliance::query()->firstOrFail();
        $this->assertSame($truck->id, $appliance->truck_id);
        $this->assertSame('Arrival-001', $appliance->unit_label);
        $this->assertSame(1, $appliance->quantity);
        $this->assertSame('0.00', $appliance->price);
        $this->assertSame('9988', $appliance->serial_number);
        $this->assertNull($appliance->status);
        $this->assertNotNull($appliance->checked_in_at);
        $this->assertSame('NEW-1', $appliance->model?->model_number);
        $this->assertSame('furniture', $appliance->category?->type->value);
        $this->assertNotNull($appliance->furnitureDetails);
        $this->assertNotNull($checkIn->fresh()->confirmed_at);
        $this->assertDatabaseHas('user_actions', [
            'action_type' => 'create_appliance',
            'item_id' => $appliance->id,
        ]);
        $this->assertDatabaseHas('user_actions', [
            'action_type' => 'check_in',
            'item_id' => $appliance->id,
        ]);
    }

    public function test_failed_extract_writes_nothing(): void
    {
        $admin = $this->userWithRole('admin');
        $this->configureOpenRouter();
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'openrouter.ai/*' => Http::response(['error' => 'unavailable'], 500),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.store'), [
                'photos' => [UploadedFile::fake()->image('label.jpg')],
            ])
            ->assertSessionHas('error', 'The label could not be read. Nothing was saved. Try the photo again.');

        $this->assertSame(0, ItemCheckIn::query()->count());
        $this->assertSame(0, TruckAppliance::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_missing_openrouter_key_writes_nothing(): void
    {
        $admin = $this->userWithRole('admin');
        config(['services.openrouter.key' => null]);
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake();

        $this->actingAs($admin)
            ->post(route('admin.inventory.check-in.store'), [
                'photos' => [UploadedFile::fake()->image('label.jpg')],
            ])
            ->assertSessionHas('error', 'Check-in needs an OpenRouter API key. Set OPENROUTER_API_KEY and try again.');

        Http::assertNothingSent();
        $this->assertSame(0, ItemCheckIn::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_viewer_cannot_confirm_and_technician_cannot_add(): void
    {
        $viewer = $this->userWithPermissions(['inventory.view']);
        $technician = $this->userWithRole('technician');
        $truck = $this->createTruck($technician, 'Floor');
        $appliance = $this->createAppliance($truck, $technician, [
            'serial_number' => '555',
            'status' => 'Testing',
        ]);

        $this->fakeLabel([
            'brand' => null,
            'model_number' => null,
            'product_name' => null,
            'serial_number' => '555',
        ]);

        $this->actingAs($viewer)
            ->post(route('admin.inventory.check-in.store'), [
                'photos' => [UploadedFile::fake()->image('label.jpg')],
            ])
            ->assertRedirect();

        $checkIn = ItemCheckIn::query()->firstOrFail();

        $this->actingAs($viewer)
            ->post(route('admin.inventory.check-in.confirm', $checkIn), [
                'intent' => 'match',
                'appliance_id' => $appliance->id,
            ])
            ->assertForbidden();

        $this->assertNull($appliance->fresh()->checked_in_at);

        $this->actingAs($technician)
            ->post(route('admin.inventory.check-in.store'), [
                'photos' => [UploadedFile::fake()->image('label.jpg')],
            ])
            ->assertRedirect();

        $technicianCheckIn = ItemCheckIn::query()->where('user_id', $technician->id)->firstOrFail();

        $this->actingAs($technician)
            ->post(route('admin.inventory.check-in.confirm', $technicianCheckIn), [
                'intent' => 'add',
                'truck_id' => $truck->id,
                'brand' => 'Ashley',
                'model_number' => 'NEW-9',
            ])
            ->assertForbidden();

        $this->assertSame(1, TruckAppliance::query()->count());
        $this->assertNull($technicianCheckIn->fresh()->confirmed_at);
    }

    /**
     * @param  array{brand: ?string, model_number: ?string, product_name: ?string, serial_number: ?string}  $label
     */
    private function fakeLabel(array $label): void
    {
        $this->configureOpenRouter();
        Storage::fake('public');
        Http::preventStrayRequests();
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($label)]],
                ],
            ]),
        ]);
    }

    private function configureOpenRouter(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.model' => 'anthropic/claude-sonnet-4.5',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->state(['role' => $role])->active()->create();
        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->state(['role' => 'user'])->active()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function createTruck(User $user, string $name): Truck
    {
        return Truck::query()->create([
            'name' => $name,
            'units_on_truck' => 1,
            'cost_of_truck' => 0,
            'shipping_cost' => 0,
            'arrival_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function createModel(User $user, string $modelNumber): ApplianceModel
    {
        return ApplianceModel::query()->create([
            'model_number' => $modelNumber,
            'status' => 1,
            'msrp' => '0.00',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAppliance(Truck $truck, User $user, array $overrides = []): TruckAppliance
    {
        return TruckAppliance::query()->create(array_merge([
            'truck_id' => $truck->id,
            'serial_number' => 'SN',
            'product_name' => 'Washer',
            'status' => 'Ready',
            'price' => 0,
            'quantity' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ], $overrides));
    }
}
