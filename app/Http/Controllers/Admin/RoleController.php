<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\DataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission('roles.view');

        $permissionModules = $this->permissionModules();
        $dataTable = $this->rolesIndexDataTable($permissionModules);

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
            'permissionModules' => $permissionModules,
            'dataTable' => $dataTable,
            'protectedRoleNames' => config('authorization.protected_role_names', []),
        ]);
    }

    public function create(): RedirectResponse
    {
        $this->authorizePermission('roles.create');

        return redirect()->route('admin.roles.index');
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => $request->validated('guard_name', 'web'),
            'description' => $request->validated('description'),
            'created_by' => $request->user()->id,
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', __('Role created successfully.'));
    }

    public function edit(Role $role): RedirectResponse
    {
        $this->authorizePermission('roles.edit');

        return redirect()->route('admin.roles.index');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if (in_array($role->name, config('authorization.protected_role_names', []), true)) {
            if ($role->name !== $request->validated('name')) {
                return redirect()->back()->with('error', __('This system role cannot be renamed.'));
            }
        }

        $role->update([
            'name' => $request->validated('name'),
            'guard_name' => $request->validated('guard_name', $role->guard_name),
            'description' => $request->validated('description'),
        ]);

        $role->syncPermissions($request->validated('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', __('Role updated successfully.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorizePermission('roles.delete');

        if (in_array($role->name, config('authorization.protected_role_names', []), true)) {
            return redirect()->route('admin.roles.index')->with('error', __('System roles cannot be deleted.'));
        }

        if ($role->users()->exists()) {
            return redirect()->route('admin.roles.index')->with('error', __('Detach all users before deleting this role.'));
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', __('Role deleted successfully.'));
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    private function permissionModules(): Collection
    {
        return Permission::query()
            ->orderBy('module_name')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => $permission->module_name ?? 'general');
    }

    /**
     * @param  Collection<string, Collection<int, Permission>>  $permissionModules
     */
    private function rolesIndexDataTable(Collection $permissionModules): DataTable
    {
        $moduleColumns = $permissionModules->keys()->map(fn (string $module) => [
            'key' => $this->permissionModuleColumnKey($module),
            'label' => Str::headline($module),
            'align' => 'center',
            'sortable' => false,
        ])->all();

        return new DataTable(
            storageKey: 'rolesIndexTableColumns',
            defaultSort: ['name', 'asc'],
            columns: [
                [
                    'key' => 'name',
                    'label' => 'Role',
                    'sortable' => false,
                ],
                [
                    'key' => 'description',
                    'label' => 'Description',
                    'sortable' => false,
                ],
                [
                    'key' => 'all',
                    'label' => 'All',
                    'align' => 'center',
                    'sortable' => false,
                ],
                ...$moduleColumns,
                [
                    'key' => 'actions',
                    'label' => 'Actions',
                    'align' => 'right',
                    'sortable' => false,
                ],
            ],
        );
    }

    private function permissionModuleColumnKey(string $module): string
    {
        return 'module-'.Str::slug($module);
    }
}
