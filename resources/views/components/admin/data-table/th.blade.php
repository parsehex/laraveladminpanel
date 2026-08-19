@props([
    'column',
    'label',
    'align' => 'left',
    'sticky' => false,
])

@php
    $alignClass = $align === 'right' ? 'text-right' : 'text-left';
@endphp

<th data-col="{{ $column }}"
    :class="{ 'hidden': !isColumnVisible('{{ $column }}') }"
    {{ $attributes->merge(['class' => "px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider {$alignClass}".($sticky ? ' sticky-action' : '')]) }}>
    {{ $label }}
</th>
