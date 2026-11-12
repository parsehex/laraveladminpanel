@props([
    'status' => null,
    'count' => null,
    'href' => null,
    'selected' => false,
    'muted' => false,
])

@php
    $status = filled($status) ? $status : 'Triage';
    $style = \App\Support\InventoryStatusAppearance::style($status, (bool) $muted);
    $label = $count === null ? $status : $status.' ('.$count.')';
    $baseClasses = 'inline-flex items-center whitespace-nowrap rounded-full border border-slate-900/20 px-2.5 py-1 text-xs font-extrabold leading-none';
@endphp

@if($href)
<a href="{{ $href }}"
   title="{{ $selected ? 'Remove '.$status.' filter' : 'Filter by '.$status }}"
   style="{{ $style }}"
   {{ $attributes->class([$baseClasses, 'transition hover:-translate-y-px hover:ring-2 hover:ring-blue-500/50', 'ring-2 ring-blue-600 is-selected' => $selected]) }}>{{ $label }}</a>
@else
<span style="{{ $style }}" {{ $attributes->class([$baseClasses]) }}>{{ $label }}</span>
@endif
