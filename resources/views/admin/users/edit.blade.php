@extends('layouts.admin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Edit User: {{ $user->name }}</h2>
            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Back to Users
            </a>
        </div>
        
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')
            
            <x-form.input name="name" label="Full Name" type="text" :value="$user->name" required="true" />
            <x-form.input name="email" label="Email Address" type="email" :value="$user->email" required="true" />
            <x-form.input name="password" label="Password" type="password" placeholder="Leave blank to keep current password" />
            <x-form.input name="password_confirmation" label="Confirm Password" type="password" placeholder="Leave blank to keep current password" />
            
            <x-form.select name="role" label="Role" :options="$roles" :value="old('role', $user->roles->first()?->name ?? $user->role)" required="true" />
            
            <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$user->status" required="true" />

            @if(isset($permissions) && $permissions->isNotEmpty())
            @php $dp = old('direct_permissions', $user->getDirectPermissions()->pluck('name')->all()); @endphp
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">Direct permissions</p>
                @foreach($permissions as $module => $items)
                    <div class="mb-3 border rounded p-3">
                        <p class="text-xs font-semibold text-gray-600 mb-2">{{ $module }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($items as $permission)
                                <label class="inline-flex items-center text-sm">
                                    <input type="checkbox" name="direct_permissions[]" value="{{ $permission->name }}" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array($permission->name, $dp, true) ? 'checked' : '' }}>
                                    <span class="ml-2">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
            
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</a>
                <x-form.button type="submit" variant="primary"><i class="fas fa-save mr-2"></i>Update User</x-form.button>
            </div>
        </form>
    </div>
</div>
@endsection
