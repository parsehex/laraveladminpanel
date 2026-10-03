@props([
    'label',
    'for' => null,
])

<div {{ $attributes }}>
    <label @if($for) for="{{ $for }}" @endif class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    {{ $slot }}
</div>
