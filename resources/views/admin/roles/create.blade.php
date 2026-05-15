@extends('layouts.admin')

@section('title', 'Create role')
@section('page-title', 'Create role')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold mb-6">Create role</h2>
    <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
        @csrf
        <x-form.input name="name" label="Role name" required="true" />
        <x-form.select name="guard_name" label="Guard" :options="array_combine(array_keys(config('auth.guards')), array_keys(config('auth.guards')))" value="web" required="true" />
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
            @error('description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <h3 class="text-lg font-medium mb-3">Permissions</h3>
            @foreach($permissions as $module => $items)
                <div class="mb-4 border rounded-md p-4">
                    <p class="font-semibold text-gray-800 mb-2">{{ $module }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($items as $permission)
                            <label class="inline-flex items-center text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" class="rounded border-gray-300 text-blue-600"
                                    {{ in_array($permission->name, old('permissions', []), true) ? 'checked' : '' }}>
                                <span class="ml-2">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            @error('permissions')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md">Cancel</a>
            <x-form.button type="submit" variant="primary">Save</x-form.button>
        </div>
    </form>
</div>
@endsection
