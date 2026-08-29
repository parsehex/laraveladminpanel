@props([
    'formId',
    'items',
    'selected' => [],
    'disabled' => false,
])

@php
    $modulePermissions = $items->pluck('name')->all();
    $moduleChecked = count(array_intersect($modulePermissions, $selected)) === count($modulePermissions) && count($modulePermissions) > 0;
@endphp

<label class="inline-flex cursor-pointer items-center justify-center {{ $disabled ? 'cursor-default opacity-70' : '' }}">
    <input type="checkbox"
           form="{{ $formId }}"
           class="js-module-permission h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
           @checked($moduleChecked)
           @disabled($disabled)>
    <span class="js-module-permission-values sr-only">
        @foreach($items as $permission)
            <input type="hidden"
                   form="{{ $formId }}"
                   name="permissions[]"
                   value="{{ $permission->name }}"
                   @disabled($disabled || ! $moduleChecked)>
        @endforeach
    </span>
</label>
