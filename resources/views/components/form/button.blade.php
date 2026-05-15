@props(['type' => 'button', 'variant' => 'primary'])

@php
    $classes = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
        'success' => 'bg-green-600 hover:bg-green-700 text-white',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white',
        'warning' => 'bg-yellow-600 hover:bg-yellow-700 text-white',
    ];
@endphp

<button type="{{ $type }}" 
        {{ $attributes->merge(['class' => 'px-4 py-2 rounded-md font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150 ease-in-out ' . $classes[$variant]]) }}>
    {{ $slot }}
</button>