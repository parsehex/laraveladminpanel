@extends('layouts.admin')

@section('title', 'Create User')
@section('page-title', 'Create New User')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Create New User</h2>
            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Back to Users
            </a>
        </div>
        
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf
            
            <x-form.input name="name" label="Full Name" type="text" required="true" />
            <x-form.input name="email" label="Email Address" type="email" required="true" />
            <x-form.input name="password" label="Password" type="password" required="true" />
            <x-form.input name="password_confirmation" label="Confirm Password" type="password" required="true" />
            <x-form.input name="registration_code" label="Registration Code" type="text" required="true" />
            
            <x-form.select name="role" label="Role" :options="$roles" required="true" />
            
            <x-form.select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" value="active" required="true" />

            <!-- @if(isset($permissions) && $permissions->isNotEmpty())
            <div>
                <p class="text-sm font-medium text-gray-700 mb-2">Direct permissions (optional)</p>
                <p class="text-xs text-gray-500 mb-3">Overrides are merged with the role via Spatie.</p>
                @foreach($permissions as $module => $items)
                    <div class="mb-3 border rounded p-3">
                        <p class="text-xs font-semibold text-gray-600 mb-2">{{ $module }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($items as $permission)
                                <label class="inline-flex items-center text-sm">
                                    <input type="checkbox" name="direct_permissions[]" value="{{ $permission->name }}" class="rounded border-gray-300 text-blue-600"
                                        {{ in_array($permission->name, old('direct_permissions', []), true) ? 'checked' : '' }}>
                                    <span class="ml-2">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            @endif -->
            
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('admin.users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</a>
                <x-form.button type="submit" variant="primary"><i class="fas fa-save mr-2"></i>Create User</x-form.button>
            </div>
        </form>
    </div>
</div>
@endsection
