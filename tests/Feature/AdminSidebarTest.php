<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_groups_links_and_splits_folder_chevrons(): void
    {
        $response = $this->actingAs($this->adminUser())->get(route('admin.dashboard'));

        $response->assertOk();
        $xpath = $this->sidebarXpath($response);
        $this->assertNavLabelOrder($xpath, [
            'Dashboard',
            'Executive Dashboard',
            'Inventory',
            'Appliances',
            'Scan',
            'Trucks',
            'Models',
            'Parts',
            'Deliveries',
            'Sales',
            'Kits',
            'Kit Parts',
            'Manage',
            'Procedures',
            'Demanufacture',
            'Testing',
            'Users',
            'Roles',
            'Notifications',
            'User Actions',
            'Locations',
            'Statuses',
        ]);

        $inventory = $this->folder($xpath, 'inventory');
        $this->assertNull($xpath->query('.//a[contains(@class, "ui-nav-folder-label")]', $inventory)->item(0));
        $this->assertInstanceOf(
            DOMElement::class,
            $xpath->query('.//button[contains(@class, "ui-nav-folder-label")]', $inventory)->item(0),
        );

        $kits = $this->folder($xpath, 'kits');
        $kitsLink = $xpath->query('.//a[contains(@class, "ui-nav-folder-label")]', $kits)->item(0);
        $this->assertInstanceOf(DOMElement::class, $kitsLink);
        $this->assertSame(route('admin.kits.index'), $kitsLink->getAttribute('href'));
        $this->assertInstanceOf(
            DOMElement::class,
            $xpath->query('.//button[contains(@class, "ui-nav-folder-chevron-btn")]', $kits)->item(0),
        );
        $this->assertInstanceOf(
            DOMElement::class,
            $xpath->query('.//a[@href="'.route('admin.kit-parts.index').'"]', $kits)->item(0),
        );

        $users = $this->folder($xpath, 'users');
        $usersLink = $xpath->query('.//a[contains(@class, "ui-nav-folder-label")]', $users)->item(0);
        $this->assertInstanceOf(DOMElement::class, $usersLink);
        $this->assertSame(route('admin.users.index'), $usersLink->getAttribute('href'));
        $this->assertInstanceOf(
            DOMElement::class,
            $xpath->query('.//a[@href="'.route('admin.roles.index').'"]', $users)->item(0),
        );
    }

    public function test_kit_parts_page_highlights_kits_and_marks_the_folder_open(): void
    {
        $response = $this->actingAs($this->adminUser())->get(route('admin.kit-parts.index'));

        $response->assertSee('const sidebarFoldersActive = ["kits"]', false);
        $xpath = $this->sidebarXpath($response);
        $this->assertClassContains($this->folderRow($xpath, 'kits'), 'is-active');
        $kitParts = $xpath->query('//a[@href="'.route('admin.kit-parts.index').'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $kitParts);
        $this->assertClassContains($kitParts, 'is-active');
    }

    public function test_kits_page_highlights_the_link_without_forcing_the_folder_open(): void
    {
        $response = $this->actingAs($this->adminUser())->get(route('admin.kits.index'));

        $response->assertSee('const sidebarFoldersActive = []', false);
        $xpath = $this->sidebarXpath($response);
        $this->assertClassContains($this->folderRow($xpath, 'kits'), 'is-active');
        $link = $xpath->query('//*[@data-folder="kits"]//a[contains(@class, "ui-nav-folder-label")]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $link);
        $this->assertClassContains($link, 'is-active');
    }

    public function test_roles_page_highlights_users_and_opens_manage(): void
    {
        $response = $this->actingAs($this->adminUser())->get(route('admin.roles.index'));

        $response->assertSee('const sidebarFoldersActive = ["manage","users"]', false);
        $xpath = $this->sidebarXpath($response);
        $this->assertClassContains($this->folderRow($xpath, 'manage'), 'is-active');
        $this->assertClassContains($this->folderRow($xpath, 'users'), 'is-active');
        $roles = $xpath->query('//a[@href="'.route('admin.roles.index').'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $roles);
        $this->assertClassContains($roles, 'is-active');
    }

    public function test_testing_flows_page_highlights_procedures_and_opens_manage(): void
    {
        $response = $this->actingAs($this->adminUser())->get(route('admin.testing-flows.index'));

        $response->assertSee('const sidebarFoldersActive = ["manage","procedures"]', false);
        $xpath = $this->sidebarXpath($response);
        $this->assertClassContains($this->folderRow($xpath, 'manage'), 'is-active');
        $this->assertClassContains($this->folderRow($xpath, 'procedures'), 'is-active');
        $testing = $xpath->query('//a[@href="'.route('admin.testing-flows.index').'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $testing);
        $this->assertClassContains($testing, 'is-active');
    }

    public function test_appliance_list_highlights_appliances_and_opens_inventory(): void
    {
        $response = $this->actingAs($this->adminUser())->get(route('admin.inventory.index'));

        $response->assertSee('const sidebarFoldersActive = ["inventory"]', false);
        $xpath = $this->sidebarXpath($response);
        $this->assertClassContains($this->folderRow($xpath, 'inventory'), 'is-active');
        $appliances = $xpath->query('//a[@href="'.route('admin.inventory.index').'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $appliances);
        $this->assertClassContains($appliances, 'is-active');
        $scan = $xpath->query('//a[@href="'.route('admin.inventory.scan').'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $scan);
        $this->assertClassNotContains($scan, 'is-active');
    }

    public function test_scan_page_highlights_scan_and_opens_inventory(): void
    {
        $response = $this->actingAs($this->adminUser())->get(route('admin.inventory.scan'));

        $response->assertSee('const sidebarFoldersActive = ["inventory"]', false);
        $xpath = $this->sidebarXpath($response);
        $this->assertClassContains($this->folderRow($xpath, 'inventory'), 'is-active');
        $scan = $xpath->query('//a[@href="'.route('admin.inventory.scan').'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $scan);
        $this->assertClassContains($scan, 'is-active');
    }

    public function test_technician_without_child_permissions_sees_plain_kits_and_users_links(): void
    {
        $response = $this->actingAs($this->technicianUser())->get(route('admin.dashboard'));

        $response->assertOk();
        $xpath = $this->sidebarXpath($response);
        $this->assertNull($xpath->query('//*[@data-folder="kits"]')->item(0));
        $this->assertNull($xpath->query('//*[@data-folder="users"]')->item(0));
        $this->assertInstanceOf(
            DOMElement::class,
            $xpath->query('//nav//a[@href="'.route('admin.kits.index').'"]')->item(0),
        );
        $this->assertInstanceOf(
            DOMElement::class,
            $xpath->query('//nav//a[@href="'.route('admin.users.index').'"]')->item(0),
        );
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->admin()->active()->create();
        $user->syncRoles(['admin']);

        return $user;
    }

    private function technicianUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->active()->create(['role' => 'technician']);
        $user->syncRoles(['technician']);

        return $user;
    }

    private function sidebarXpath(TestResponse $response): DOMXPath
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();

        return new DOMXPath($document);
    }

    private function folder(DOMXPath $xpath, string $id): DOMElement
    {
        $folder = $xpath->query('//aside[contains(@class, "ui-sidebar")]//*[@data-folder="'.$id.'"]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $folder);

        return $folder;
    }

    private function folderRow(DOMXPath $xpath, string $id): DOMElement
    {
        $row = $xpath->query('//*[@data-folder="'.$id.'"]/*[contains(@class, "ui-nav-folder-row")]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $row);

        return $row;
    }

    private function assertClassContains(DOMElement $element, string $class): void
    {
        $this->assertContains($class, $this->classList($element));
    }

    private function assertClassNotContains(DOMElement $element, string $class): void
    {
        $this->assertNotContains($class, $this->classList($element));
    }

    /**
     * @return list<string>
     */
    private function classList(DOMElement $element): array
    {
        $classes = preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];

        return array_values(array_filter($classes, fn (string $class): bool => $class !== ''));
    }

    /**
     * @param  list<string>  $labels
     */
    private function assertNavLabelOrder(DOMXPath $xpath, array $labels): void
    {
        $nodes = $xpath->query('//aside[contains(@class, "ui-sidebar")]//nav//span');
        $texts = [];

        foreach ($nodes as $node) {
            $text = trim($node->textContent);

            if ($text !== '') {
                $texts[] = $text;
            }
        }

        $cursor = 0;

        foreach ($labels as $label) {
            $index = array_search($label, array_slice($texts, $cursor), true);
            $this->assertNotFalse($index, 'Missing sidebar label ['.$label.'] in: '.implode(', ', $texts));
            $cursor += $index + 1;
        }
    }
}
