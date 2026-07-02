<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('roles.view');

        $query = Role::query()->withCount('permissions');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereLike('name', '%'.$search.'%');
        }

        $roles = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $this->authorizePermission('roles.create');

        $permissions = Permission::query()
            ->orderBy('module_name')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p) => $p->module_name ?? 'general');

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request)
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

    public function edit(Role $role)
    {
        $this->authorizePermission('roles.edit');

        $permissions = Permission::query()
            ->orderBy('module_name')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p) => $p->module_name ?? 'general');

        $role->load('permissions');

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
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

    public function destroy(Role $role)
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
}
