@extends('layouts.admin')

@section('title', 'Edit role')
@section('page-title', 'Edit: '.$role->name)

@section('page-actions')
    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center text-sm font-semibold text-slate-600 hover:text-slate-900">
        <i class="fas fa-arrow-left mr-1"></i>Back
    </a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6">
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
            @php $selected = old('permissions', $role->permissions->pluck('name')->all()); @endphp
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-medium">Permissions</h3>
                <label class="inline-flex items-center text-sm font-semibold text-gray-800">
                    <input type="checkbox" class="rounded border-gray-300 text-blue-600 js-select-all-permissions">
                    <span class="ml-2">Select All</span>
                </label>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($permissions as $module => $items)
                    @php
                        $modulePermissions = $items->pluck('name')->all();
                        $moduleChecked = count(array_intersect($modulePermissions, $selected)) > 0;
                    @endphp
                    <div class="border rounded-md p-4 js-permission-card">
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
            </div>
            @error('permissions')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md">Cancel</a>
            <x-form.button type="submit" variant="primary">Update</x-form.button>
        </div>
    </form>
</div>
<script>
    const moduleCheckboxes = document.querySelectorAll('.js-module-permission');
    const selectAll = document.querySelector('.js-select-all-permissions');

    const updateSelectAll = () => {
        if (!selectAll) return;
        selectAll.checked = [...moduleCheckboxes].length > 0 && [...moduleCheckboxes].every((checkbox) => checkbox.checked);
        selectAll.indeterminate = [...moduleCheckboxes].some((checkbox) => checkbox.checked) && !selectAll.checked;
    };

    moduleCheckboxes.forEach((checkbox) => {
        const values = checkbox.closest('.js-permission-card').querySelectorAll('.js-module-permission-values input');
        const togglePermissions = () => {
            values.forEach((input) => input.disabled = !checkbox.checked);
            updateSelectAll();
        };

        togglePermissions();
        checkbox.addEventListener('change', togglePermissions);
    });

    selectAll?.addEventListener('change', () => {
        const shouldCheck = selectAll.checked;
        moduleCheckboxes.forEach((checkbox) => {
            checkbox.checked = shouldCheck;
            checkbox.dispatchEvent(new Event('change'));
        });
        selectAll.checked = shouldCheck;
        selectAll.indeterminate = false;
    });
</script>
@endsection
