<?php

namespace Tests\Feature;

use App\Legacy\LegacyDumpReader;
use Database\Seeders\FlowSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(FlowSeeder::class);
    }

    public function test_dump_reader_parses_copy_blocks(): void
    {
        $dump = new LegacyDumpReader(base_path('tests/fixtures/legacy-minimal.sql'));

        $this->assertSame(1, $dump->count('trucks'));
        $this->assertSame(1, $dump->count('truck_items'));

        $rows = iterator_to_array($dump->rows('truck_items'));

        $this->assertSame('FT-001-001', $rows[0]['unit_label']);
        $this->assertSame('Triage', $rows[0]['current_status']);
    }

    public function test_report_only_command_succeeds(): void
    {
        $this->artisan('legacy:import', [
            '--data' => base_path('tests/fixtures/legacy-minimal.sql'),
            '--report-only' => true,
        ])->assertSuccessful()
            ->expectsOutputToContain('Report-only scan complete');
    }

    public function test_import_resets_and_loads_fixture_rows(): void
    {
        $this->artisan('legacy:import', [
            '--data' => base_path('tests/fixtures/legacy-minimal.sql'),
            '--reset' => true,
            '--allow-unresolved' => true,
        ])->assertSuccessful();

        $this->assertDatabaseCount('trucks', 1);
        $this->assertDatabaseCount('truck_appliances', 1);
        $this->assertDatabaseHas('truck_appliances', [
            'id' => 1,
            'unit_label' => 'FT-001-001',
            'status' => 'Triage',
        ]);
    }
}
