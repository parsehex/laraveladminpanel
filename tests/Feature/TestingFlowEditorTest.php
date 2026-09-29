<?php

namespace Tests\Feature;

use App\Models\TestingFlow;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestingFlowEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_defaults_to_classic_editor(): void
    {
        $flow = $this->createFlow();

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.testing-flows.edit', $flow->slug));

        $response->assertOk();
        $response->assertSee('id="flow-editor"', false);
        $response->assertDontSee('id="procedure-editor"', false);
        $response->assertSee('Canvas editor', false);
        $response->assertSee(
            'href="'.route('admin.testing-flows.edit', ['flow' => $flow->slug, 'editor' => 'canvas']).'"',
            false,
        );
    }

    public function test_edit_page_can_open_canvas_editor(): void
    {
        $flow = $this->createFlow();

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.testing-flows.edit', ['flow' => $flow->slug, 'editor' => 'canvas']));

        $response->assertOk();
        $response->assertSee('id="procedure-editor"', false);
        $response->assertSee('data-flow=', false);
        $response->assertSee('data-statuses=', false);
        $response->assertSee('data-form-action="'.route('admin.testing-flows.update', $flow->slug).'"', false);
        $response->assertSee('Classic editor', false);
        $response->assertDontSee('id="flow-editor"', false);
    }

    public function test_update_persists_flow_json_and_bumps_version(): void
    {
        $flow = $this->createFlow();

        $payload = [
            'slug' => $flow->slug,
            'name' => 'Washers Updated',
            'version' => 1,
            'updated_at' => null,
            'start' => '1',
            'steps' => [
                '1' => [
                    'id' => '1',
                    'question' => 'Does it power on?',
                    'type' => 'radio',
                    'note' => false,
                    'options' => [
                        [
                            'key' => 'yes',
                            'text' => 'Yes',
                            'next' => 'pass',
                            'status' => null,
                        ],
                        [
                            'key' => 'no',
                            'text' => 'No',
                            'next' => 'repair',
                            'status' => null,
                        ],
                    ],
                ],
                'pass' => [
                    'id' => 'pass',
                    'question' => 'Pass',
                    'type' => 'none',
                    'note' => false,
                    'next' => null,
                    'status' => 'Show Room',
                    'options' => [],
                ],
                'repair' => [
                    'id' => 'repair',
                    'question' => 'Repair',
                    'type' => 'none',
                    'note' => false,
                    'next' => null,
                    'status' => 'Repair',
                    'options' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.testing-flows.update', $flow->slug), [
                'name' => 'Washers Updated',
                'start' => '1',
                'flow_json' => json_encode($payload),
            ]);

        $response->assertRedirect(route('admin.testing-flows.edit', $flow->slug));

        $flow->refresh();
        $this->assertSame(2, $flow->version);
        $this->assertSame('Washers Updated', $flow->name);
        $this->assertSame('1', $flow->start);
        $this->assertSame('Does it power on?', $flow->steps['1']['question']);
        $this->assertCount(1, $flow->versions);
    }

    public function test_canvas_save_redirects_back_to_canvas_editor(): void
    {
        $flow = $this->createFlow();

        $payload = [
            'slug' => $flow->slug,
            'name' => 'Washers',
            'version' => 1,
            'updated_at' => null,
            'start' => '1',
            'steps' => $flow->steps,
        ];

        $response = $this->actingAs($this->adminUser())
            ->put(route('admin.testing-flows.update', $flow->slug), [
                'name' => 'Washers',
                'start' => '1',
                'editor' => 'canvas',
                'flow_json' => json_encode($payload),
            ]);

        $response->assertRedirect(route('admin.testing-flows.edit', [
            'flow' => $flow->slug,
            'editor' => 'canvas',
        ]));
    }

    public function test_guest_cannot_open_editor(): void
    {
        $flow = $this->createFlow();

        $this->get(route('admin.testing-flows.edit', $flow->slug))
            ->assertRedirect();
    }

    private function createFlow(): TestingFlow
    {
        return TestingFlow::query()->create([
            'slug' => 'washers',
            'name' => 'Washers',
            'version' => 1,
            'start' => '1',
            'steps' => [
                '1' => [
                    'id' => '1',
                    'question' => 'Initial question',
                    'type' => 'radio',
                    'note' => false,
                    'options' => [
                        [
                            'key' => 'yes',
                            'text' => 'Yes',
                            'next' => null,
                            'status' => 'Show Room',
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }
}
