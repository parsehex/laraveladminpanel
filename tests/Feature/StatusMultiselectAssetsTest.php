<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StatusMultiselectAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trucks_index_renders_alpine_status_multiselect(): void
    {
        Permission::findOrCreate('trucks.view');

        $user = User::factory()->create();
        $user->givePermissionTo('trucks.view');

        $response = $this->actingAs($user)->get(route('admin.trucks.index'));

        $response->assertOk();
        $response->assertSee('data-status-filter', false);
        $response->assertSee('x-data="{ open: false }"', false);
        $response->assertSee('@click="open = !open"', false);
    }
}
