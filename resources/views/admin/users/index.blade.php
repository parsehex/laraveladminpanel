@extends('layouts.admin')

@section('title', 'Users Management')
@section('page-title', 'Users Management')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Users Management</h1>
        @canAccess('users.create')
        <a href="{{ route('admin.users.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Add New User
        </a>
        @endcanAccess
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Search by name or email..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Roles</option>
                    @foreach($filterRoles as $roleName)
                        <option value="{{ $roleName }}" {{ request('role') === $roleName ? 'selected' : '' }}>{{ $roleName }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-undo mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php $rn = $user->roles->first()?->name ?? $user->role; @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $rn }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $user->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            @canAccess('users.view')
                            <a href="{{ route('admin.users.show', $user) }}" class="text-blue-600 hover:text-blue-900"><i class="fas fa-eye"></i></a>
                            @endcanAccess
                            @canAccess('users.edit')
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-green-600 hover:text-green-900"><i class="fas fa-edit"></i></a>
                            @endcanAccess
                            @canAccess('users.delete')
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                            @endcanAccess
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-users text-4xl text-gray-300 mb-2"></i>
                                <p>No users found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
