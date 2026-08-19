@props([
    'column',
    'label',
    'align' => 'left',
    'sort' => null,
    'direction' => null,
    'sticky' => false,
])

@php
    $activeSort = $sort ?? request('sort');
    $activeDirection = request('direction', 'asc');

    if ($activeSort === $column && $activeDirection === 'desc') {
        $sortUrl = request()->fullUrlWithQuery([
            'sort' => null,
            'direction' => null,
            'page' => null,
        ]);
    } elseif ($activeSort === $column) {
        $sortUrl = request()->fullUrlWithQuery([
            'sort' => $column,
            'direction' => 'desc',
            'page' => null,
        ]);
    } else {
        $sortUrl = request()->fullUrlWithQuery([
            'sort' => $column,
            'direction' => 'asc',
            'page' => null,
        ]);
    }

    $alignClass = $align === 'right' ? 'text-right' : 'text-left';
@endphp

<th data-col="{{ $column }}"
    :class="{ 'hidden': !isColumnVisible('{{ $column }}') }"
    {{ $attributes->merge(['class' => "px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider {$alignClass}".($sticky ? ' sticky-action' : '')]) }}>
    <a href="{{ $sortUrl }}" class="group inline-flex items-center gap-1 hover:text-gray-800 {{ $align === 'right' ? 'float-right' : '' }}">
        <span>{{ $label }}</span>
        @if($activeSort === $column)
            <i class="fas fa-sort-{{ $activeDirection === 'asc' ? 'up' : 'down' }} text-blue-600"></i>
        @else
            <i class="fas fa-sort text-gray-400 group-hover:text-gray-600"></i>
        @endif
    </a>
</th>
