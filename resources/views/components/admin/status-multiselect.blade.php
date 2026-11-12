@props([
    'name' => 'status[]',
    'options' => [],
    'selected' => [],
    'label' => 'Status',
    'placeholder' => 'Select status',
])

@php
    $selected = collect($selected)->filter()->values()->all();
    $selectedCount = count($selected);
@endphp

<div {{ $attributes }}>
    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <div class="relative" data-status-filter x-data="{ open: false }" @click.outside="open = false">
        <button type="button"
                @click="open = !open"
                class="w-full min-h-[42px] px-3 py-2 border border-gray-300 rounded-md text-left bg-white text-gray-800 shadow-sm flex items-center justify-between gap-2 hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
            @if($selectedCount)
                <span class="flex min-w-0 flex-wrap items-center gap-1">
                    @foreach($selected as $selectedStatus)
                        <x-admin.status-chip :status="$selectedStatus" />
                    @endforeach
                </span>
            @else
                <span class="truncate">{{ $placeholder }}</span>
            @endif
            <i class="fas fa-chevron-down shrink-0 text-xs text-blue-600"></i>
        </button>
        <div x-cloak
             x-show="open"
             class="absolute top-[calc(100%+0.5rem)] right-0 z-[100001] w-72 max-w-[calc(100vw-2rem)] rounded-lg border border-gray-200 bg-white p-2 shadow-2xl ring-1 ring-black/5 max-h-72 overflow-y-auto">
            @foreach($options as $option)
                <label class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-blue-50">
                    <input type="checkbox" name="{{ $name }}" value="{{ $option }}" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(in_array($option, $selected, true))>
                    <x-admin.status-chip :status="$option" />
                </label>
            @endforeach
        </div>
    </div>
</div>
