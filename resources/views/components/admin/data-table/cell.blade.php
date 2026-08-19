@props([
    'column',
    'align' => 'left',
    'sticky' => false,
    'truncate' => false,
])

@php
    $alignClass = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
    $truncateClass = $truncate ? 'max-w-[9rem] truncate' : 'whitespace-nowrap';
@endphp

<td data-col="{{ $column }}"
    :class="{ 'hidden': !isColumnVisible('{{ $column }}') }"
    {{ $attributes->merge(['class' => "px-4 py-3 text-sm text-gray-700 {$alignClass} {$truncateClass}".($sticky ? ' sticky-action' : '')]) }}>
    {{ $slot }}
</td>
