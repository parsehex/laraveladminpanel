<?php

namespace Tests\Feature;

use App\Models\Model;
use App\Models\Part;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PartImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_import_parts_from_csv(): void
    {
        $user = $this->adminUser();

        $csv = <<<'CSV'
URL,Part Number,Product Name,Retail Price,Your Price,Images,Cross Reference Information,Models it applies to
https://example.com/parts/W10820039,W10820039,Washer Drive Belt,49.99,28.50,,AP5983729,WTW5000DW1
CSV;

        $this->importAndConfirm($user, $csv)
            ->assertRedirect(route('admin.parts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('parts', 1);
        $this->assertDatabaseHas('parts', [
            'part_number' => 'W10820039',
            'product_name' => 'Washer Drive Belt',
            'retail_price' => 49.99,
            'your_price' => 28.50,
            'total_stock' => 0,
        ]);
    }

    public function test_upload_redirects_to_review_without_writing_parts(): void
    {
        $user = $this->adminUser();

        $csv = <<<'CSV'
URL,Part Number,Product Name,Retail Price,Your Price,Images,Cross Reference Information,Models it applies to
https://example.com/parts/W10820039,W10820039,Washer Drive Belt,49.99,28.50,,AP5983729,WTW5000DW1
CSV;

        $this->actingAs($user)->post(route('admin.parts.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('parts.csv', $csv),
        ])->assertRedirect(route('admin.parts.import.review'));

        $this->assertDatabaseCount('parts', 0);

        $this->actingAs($user)
            ->get(route('admin.parts.import.review'))
            ->assertOk()
            ->assertSee('Will create')
            ->assertSee('W10820039');
    }

    public function test_import_updates_existing_part_when_part_number_matches(): void
    {
        $user = $this->adminUser();

        Part::query()->create([
            'part_number' => 'W10820039',
            'product_name' => 'Old Name',
            'retail_price' => 10,
            'your_price' => 5,
            'total_stock' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $csv = <<<'CSV'
URL,Part Number,Product Name,Retail Price,Your Price,Images,Cross Reference Information,Models it applies to
https://example.com/parts/W10820039,W10820039,Washer Drive Belt,49.99,28.50,,AP5983729,WTW5000DW1
CSV;

        $this->importAndConfirm($user, $csv)->assertRedirect();

        $this->assertDatabaseCount('parts', 1);
        $this->assertDatabaseHas('parts', [
            'part_number' => 'W10820039',
            'product_name' => 'Washer Drive Belt',
            'retail_price' => 49.99,
        ]);
    }

    public function test_confirm_is_rejected_when_preview_has_errors(): void
    {
        $user = $this->adminUser();

        $csv = <<<'CSV'
URL,Part Number,Product Name,Retail Price,Your Price,Images,Cross Reference Information,Models it applies to
https://example.com/parts/W10820039,W10820039,Washer Drive Belt,not-a-price,28.50,,AP5983729,WTW5000DW1
CSV;

        $this->actingAs($user)->post(route('admin.parts.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('parts.csv', $csv),
        ])->assertRedirect(route('admin.parts.import.review'));

        $this->actingAs($user)
            ->post(route('admin.parts.import.confirm'))
            ->assertRedirect(route('admin.parts.import.review'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('parts', 0);
    }

    public function test_confirm_allowed_when_only_model_link_side_effects_remain(): void
    {
        $user = $this->adminUser();

        $model = Model::query()->create([
            'model_number' => 'WTW5000DW1',
            'msrp' => '399.00',
            'status' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $part = Part::query()->create([
            'part_number' => 'W10820039',
            'product_name' => 'Washer Drive Belt',
            'retail_price' => 49.99,
            'your_price' => 28.50,
            'total_stock' => 0,
            'cross_reference' => 'AP5983729',
            'model_compatibility' => 'WTW5000DW1',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $csv = <<<'CSV'
URL,Part Number,Product Name,Retail Price,Your Price,Images,Cross Reference Information,Models it applies to
https://example.com/parts/W10820039,W10820039,Washer Drive Belt,49.99,28.50,,AP5983729,WTW5000DW1
CSV;

        $this->actingAs($user)->post(route('admin.parts.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('parts.csv', $csv),
        ])->assertRedirect(route('admin.parts.import.review'));

        $this->actingAs($user)
            ->get(route('admin.parts.import.review'))
            ->assertOk()
            ->assertSee('Will apply side effects')
            ->assertSee('Link model WTW5000DW1')
            ->assertSee('action="'.route('admin.parts.import.confirm').'"', false);

        $this->actingAs($user)
            ->post(route('admin.parts.import.confirm'))
            ->assertRedirect(route('admin.parts.index'));

        $this->assertTrue($part->fresh()->models()->where('models.id', $model->id)->exists());
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
        $this->actingAs($user)->post(route('admin.parts.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('parts.csv', $csv),
        ])->assertRedirect(route('admin.parts.import.review'));

        return $this->actingAs($user)->post(route('admin.parts.import.confirm'));
    }
}
