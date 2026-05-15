@props(['type' => 'info'])

@php
    $classes = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
    ];
    
    $icons = [
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'border rounded-md p-4 ' . $classes[$type]]) }}>
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="{{ $icons[$type] }}"></i>
        </div>
        <div class="ml-3">
            {{ $slot }}
        </div>
    </div>
</div>