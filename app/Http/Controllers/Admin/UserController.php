<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->get('role')));
        }

        $users = $query->latest()->paginate(10)->withQueryString();
        $filterRoles = Role::query()->orderBy('name')->pluck('name');

        return view('admin.users.index', compact('users', 'filterRoles'));
    }

    public function create()
    {
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name', 'name');
        $permissions = Permission::query()->orderBy('module_name')->orderBy('name')->get()->groupBy(fn ($p) => $p->module_name ?? 'general');

        return view('admin.users.create', compact('roles', 'permissions'));
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $roleName = $data['role'];
        $direct = $data['direct_permissions'] ?? [];
        unset($data['direct_permissions']);

        $user = User::create($data);
        $user->syncRoles([$roleName]);
        $user->syncPermissions($direct);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load(['roles', 'permissions']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name', 'name');
        $permissions = Permission::query()->orderBy('module_name')->orderBy('name')->get()->groupBy(fn ($p) => $p->module_name ?? 'general');
        $user->load(['roles', 'permissions']);

        return view('admin.users.edit', compact('user', 'roles', 'permissions'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $roleName = $data['role'];
        $direct = $data['direct_permissions'] ?? [];
        unset($data['direct_permissions']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$roleName]);
        $user->syncPermissions($direct);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
