@extends('layouts.admin')

@section('title', 'Roles')
@section('page-title', 'Roles & permissions')

@section('page-actions')
    @canAccess('roles.create')
    <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>New role
    </a>
    @endcanAccess
@endsection

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guard</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissions</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($roles as $role)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $role->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $role->guard_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $role->permissions_count }}</td>
                        <td class="px-6 py-4 text-sm text-right space-x-2">
                            @canAccess('roles.edit')
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-green-600 hover:text-green-900"><i class="fas fa-edit"></i></a>
                            @endcanAccess
                            @canAccess('roles.delete')
                            @unless(in_array($role->name, config('authorization.protected_role_names', [])))
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline" onsubmit="return confirm('Delete this role?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                            </form>
                            @endunless
                            @endcanAccess
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No roles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-admin.table-pagination :paginator="$roles" />
    </div>
</div>
@endsection
