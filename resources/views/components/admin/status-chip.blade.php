@props([
    'status' => null,
    'count' => null,
    'href' => null,
    'selected' => false,
])

@php
    $status = filled($status) ? $status : 'Triage';
    $colorClasses = match (strtolower($status)) {
        'testing' => 'bg-[#67d4f1] text-[#083344]',
        'ready' => 'bg-[#3b82f6] text-white',
        'show room' => 'bg-[#9333ea] text-white',
        'holding' => 'bg-[#ec4899] text-white',
        'repair' => 'bg-[#f97316] text-gray-900',
        'holding for parts' => 'bg-[#eab308] text-gray-900',
        'cleaning' => 'bg-[#7c2d12] text-white',
        'demanufacture', 'breakdown' => 'bg-[#dc2626] text-white',
        'scrap' => 'bg-[#111827] text-white',
        'quality control qc' => 'bg-[#16a34a] text-[#052e16]',
        'sold' => 'bg-[#059669] text-white',
        default => 'bg-white text-gray-900',
    };
    $label = $count === null ? $status : $status.' ('.$count.')';
    $baseClasses = 'inline-flex items-center whitespace-nowrap rounded-full border border-slate-900/20 px-2.5 py-1 text-xs font-extrabold leading-none '.$colorClasses;
@endphp

@if($href)
<a href="{{ $href }}"
   title="{{ $selected ? 'Remove '.$status.' filter' : 'Filter by '.$status }}"
   {{ $attributes->class([$baseClasses, 'transition hover:-translate-y-px hover:ring-2 hover:ring-blue-500/50', 'ring-2 ring-blue-600' => $selected]) }}>{{ $label }}</a>
@else
<span {{ $attributes->class([$baseClasses]) }}>{{ $label }}</span>
@endif
