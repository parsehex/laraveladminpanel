@props([
    'action',
    'resetUrl' => null,
    'preserve' => ['sort', 'direction'],
    'hidden' => [],
    'bare' => false,
    'formClass' => 'grid grid-cols-1 gap-4',
    'actionsClass' => 'flex flex-wrap items-end justify-end gap-2',
])

@php
    $resetUrl ??= $action;
    $preserveKeys = collect($preserve)
        ->mapWithKeys(function ($value, $key) {
            if (is_int($key)) {
                return [$value => request($value)];
            }

            return [$key => $value];
        })
        ->filter(fn ($value) => $value !== null && $value !== '');
@endphp

<div {{ $attributes->class([
    'admin-filter-bar relative z-[100000] overflow-visible',
    'bg-white rounded-lg shadow p-6' => ! $bare,
]) }}>
    <form method="GET" action="{{ $action }}" class="{{ $formClass }}">
        @foreach($preserveKeys as $name => $value)
            @if(is_array($value))
                @foreach($value as $nestedValue)
                    <input type="hidden" name="{{ $name }}[]" value="{{ $nestedValue }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach

        @foreach($hidden as $name => $value)
            @if(is_array($value))
                @foreach($value as $nestedValue)
                    <input type="hidden" name="{{ $name }}[]" value="{{ $nestedValue }}">
                @endforeach
            @elseif($value !== null && $value !== '')
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach

        {{ $slot }}

        @isset($actions)
            {{ $actions }}
        @else
            <div class="{{ $actionsClass }}">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Filter</button>
                <a href="{{ $resetUrl }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Reset</a>
            </div>
        @endisset
    </form>
</div>
