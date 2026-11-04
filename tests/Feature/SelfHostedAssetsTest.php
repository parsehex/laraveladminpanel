<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SelfHostedAssetsTest extends TestCase
{
    use RefreshDatabase;

    private const THIRD_PARTY_ASSET_HOSTS = [
        'cdnjs.cloudflare.com',
        'cdn.jsdelivr.net',
        'unpkg.com',
        'fonts.googleapis.com',
        'fonts.gstatic.com',
    ];

    public function test_every_vendor_asset_referenced_by_a_view_exists(): void
    {
        $referencedPaths = [];

        foreach (File::allFiles(resource_path('views')) as $view) {
            preg_match_all("/asset\\('(vendor\\/[^']+)'\\)/", $view->getContents(), $matches);
            foreach ($matches[1] as $path) {
                $referencedPaths[$path] = $view->getRelativePathname();
            }
        }

        $this->assertNotEmpty($referencedPaths);

        foreach ($referencedPaths as $path => $viewName) {
            $this->assertFileExists(public_path($path), "{$viewName} references missing public/{$path}");
        }
    }

    public function test_admin_layout_loads_assets_from_this_site(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->admin()->active()->create();
        $admin->syncRoles(['admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee(asset('vendor/jquery-3.6.0/jquery.min.js'), false);
        $response->assertSee(asset('vendor/alpinejs-3.17.5/cdn.min.js'), false);
        $response->assertSee(asset('vendor/inter-v20/inter.css'), false);
        $this->assertNoThirdPartyAssetHosts($response->getContent());
    }

    public function test_login_page_loads_assets_from_this_site(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(asset('vendor/fontawesome-6.4.0/css/all.min.css'), false);
        $this->assertNoThirdPartyAssetHosts($response->getContent());
    }

    private function assertNoThirdPartyAssetHosts(string $html): void
    {
        foreach (self::THIRD_PARTY_ASSET_HOSTS as $host) {
            $this->assertStringNotContainsString($host, $html);
        }
    }
}
