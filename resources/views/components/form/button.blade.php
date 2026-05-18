@props(['type' => 'button', 'variant' => 'primary'])

@php
    $classes = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white shadow-sm',
        'success' => 'bg-green-600 hover:bg-green-700 text-white shadow-sm',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white shadow-sm',
        'warning' => 'bg-yellow-600 hover:bg-yellow-700 text-white shadow-sm',
    ];
@endphp

<button type="{{ $type }}" 
        {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-md text-sm font-semibold focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition duration-150 ease-in-out ' . $classes[$variant]]) }}>
    {{ $slot }}
</button>
