@extends('layouts.admin')

@section('title', 'Edit role')
@section('page-title', 'Edit role')

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold mb-6">Edit: {{ $role->name }}</h2>
    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <x-form.input name="name" label="Role name" :value="old('name', $role->name)" required="true" />
        {{-- <x-form.select name="guard_name" label="Guard" :options="array_combine(array_keys(config('auth.guards')), array_keys(config('auth.guards')))" :value="old('guard_name', $role->guard_name)" required="true" /> --}}
        <input type="hidden" name="guard_name" value="web">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea name="description" rows="3" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $role->description) }}</textarea>
            @error('description')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <h3 class="text-lg font-medium mb-3">Permissions</h3>
            @php $selected = old('permissions', $role->permissions->pluck('name')->all()); @endphp
            @foreach($permissions as $module => $items)
                @php
                    $modulePermissions = $items->pluck('name')->all();
                    $moduleChecked = count(array_intersect($modulePermissions, $selected)) > 0;
                @endphp
                <div class="mb-4 border rounded-md p-4">
                    <label class="inline-flex items-center text-sm font-semibold text-gray-800">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 js-module-permission" {{ $moduleChecked ? 'checked' : '' }}>
                        <span class="ml-2">{{ $module }}</span>
                    </label>
                    <div class="js-module-permission-values">
                        @foreach($items as $permission)
                            <input type="hidden" name="permissions[]" value="{{ $permission->name }}" {{ $moduleChecked ? '' : 'disabled' }}>
                        @endforeach
                    </div>
                </div>
            @endforeach
            @error('permissions')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md">Cancel</a>
            <x-form.button type="submit" variant="primary">Update</x-form.button>
        </div>
    </form>
</div>
<script>
    document.querySelectorAll('.js-module-permission').forEach((checkbox) => {
        const values = checkbox.closest('div').querySelectorAll('.js-module-permission-values input');
        const togglePermissions = () => values.forEach((input) => input.disabled = !checkbox.checked);

        togglePermissions();
        checkbox.addEventListener('change', togglePermissions);
    });
</script>
@endsection
