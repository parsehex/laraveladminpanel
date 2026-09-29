<?php

namespace Tests\Feature;

use App\Models\KitCatalogPart;
use App\Models\KitInventory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class KitCatalogPartImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_import_kit_parts_from_csv(): void
    {
        $user = $this->adminUser();

        $csv = <<<'CSV'
URL,Part Number,Product Name,Retail Price,Your Price,Images,Cross Reference Information,Models it applies to
https://example.com/parts/W10820039,W10820039,Washer Drive Belt,49.99,28.50,,AP5983729,WTW5000DW1
CSV;

        $this->importAndConfirm($user, $csv)
            ->assertRedirect(route('admin.kit-parts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('kit_catalog_parts', 1);
        $this->assertDatabaseHas('kit_catalog_parts', [
            'part_number' => 'W10820039',
            'product_name' => 'Washer Drive Belt',
            'retail_price' => 49.99,
            'your_price' => 28.50,
        ]);
        $this->assertDatabaseHas('kit_inventory', [
            'part_name' => 'W10820039',
            'current_stock' => 0,
        ]);
    }

    public function test_upload_redirects_to_review_without_writing_kit_parts(): void
    {
        $user = $this->adminUser();

        $csv = <<<'CSV'
URL,Part Number,Product Name,Retail Price,Your Price,Images,Cross Reference Information,Models it applies to
https://example.com/parts/W10820039,W10820039,Washer Drive Belt,49.99,28.50,,AP5983729,WTW5000DW1
CSV;

        $this->actingAs($user)->post(route('admin.kit-parts.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('kit-parts.csv', $csv),
        ])->assertRedirect(route('admin.kit-parts.import.review'));

        $this->assertDatabaseCount('kit_catalog_parts', 0);
        $this->assertDatabaseCount('kit_inventory', 0);

        $this->actingAs($user)
            ->get(route('admin.kit-parts.import.review'))
            ->assertOk()
            ->assertSee('Will create')
            ->assertSee('W10820039');
    }

    public function test_import_updates_existing_kit_part_and_inventory(): void
    {
        $user = $this->adminUser();

        KitCatalogPart::query()->create([
            'part_number' => 'W10820039',
            'product_name' => 'Old Name',
            'retail_price' => 10,
            'your_price' => 5,
            'total_stock' => 2,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        KitInventory::query()->create([
            'part_name' => 'W10820039',
            'current_stock' => 2,
            'min_level' => 0,
        ]);

        $csv = <<<'CSV'
URL,Part Number,Product Name,Retail Price,Your Price,Images,Cross Reference Information,Models it applies to,Total Stock
https://example.com/parts/W10820039,W10820039,Washer Drive Belt,49.99,28.50,,AP5983729,WTW5000DW1,7
CSV;

        $this->importAndConfirm($user, $csv)->assertRedirect();

        $this->assertDatabaseCount('kit_catalog_parts', 1);
        $this->assertDatabaseHas('kit_catalog_parts', [
            'part_number' => 'W10820039',
            'product_name' => 'Washer Drive Belt',
            'total_stock' => 7,
        ]);
        $this->assertDatabaseHas('kit_inventory', [
            'part_name' => 'W10820039',
            'current_stock' => 7,
        ]);
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function importAndConfirm(User $user, string $csv): TestResponse
    {
        $this->actingAs($user)->post(route('admin.kit-parts.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('kit-parts.csv', $csv),
        ])->assertRedirect(route('admin.kit-parts.import.review'));

        return $this->actingAs($user)->post(route('admin.kit-parts.import.confirm'));
    }
}
