@extends('layouts.admin')

@php
    $status = $appliance->status ?: 'Triage';
    $modelNumber = $appliance->model?->model_number;
    $productName = trim($appliance->product_name ?: ($appliance->model?->product_name ?? ''));
    $heading = trim(implode(' ', array_filter([$appliance->brand, $modelNumber ?: ('#'.$appliance->id)])));
    $facts = [
        ['label' => 'Label', 'value' => $appliance->unit_label],
        ['label' => 'Brand', 'value' => $appliance->brand],
        ['label' => 'Model', 'value' => $modelNumber],
        ['label' => 'Serial', 'value' => $appliance->serial_number],
        ['label' => 'Location', 'value' => $appliance->location],
        ['label' => 'Truck', 'value' => $appliance->truck?->name],
        ['label' => 'Category', 'value' => $appliance->category?->name],
    ];
    $workflow = match ($status) {
        'Testing' => ['label' => 'Start Testing', 'url' => route('admin.inventory.testing.show', $appliance)],
        'Repair' => ['label' => 'Open Repair', 'url' => route('admin.inventory.repair.show', $appliance)],
        'Demanufacture' => ['label' => 'Open Demanufacture', 'url' => route('admin.inventory.deman.show', $appliance)],
        default => null,
    };
    $statusTones = [
        'Triage' => 'border-gray-300 bg-white text-gray-900',
        'Testing' => 'border-sky-300 bg-sky-100 text-sky-950',
        'Repair' => 'border-orange-300 bg-orange-100 text-orange-950',
        'Breakdown' => 'border-stone-300 bg-stone-100 text-stone-950',
        'Demanufacture' => 'border-red-300 bg-red-100 text-red-950',
        'Cleaning' => 'border-amber-300 bg-amber-100 text-amber-950',
        'Ready' => 'border-blue-300 bg-blue-100 text-blue-950',
        'Scrap' => 'border-gray-800 bg-gray-800 text-white',
        'Show Room' => 'border-purple-300 bg-purple-100 text-purple-950',
        'Sent To Ebay' => 'border-indigo-300 bg-indigo-100 text-indigo-950',
        'Video' => 'border-fuchsia-300 bg-fuchsia-100 text-fuchsia-950',
        'Quality Control QC' => 'border-green-300 bg-green-100 text-green-950',
        'Sold' => 'border-emerald-700 bg-emerald-700 text-white',
        'Holding for parts' => 'border-yellow-300 bg-yellow-100 text-yellow-950',
        'Holding' => 'border-pink-300 bg-pink-100 text-pink-950',
    ];
@endphp

@section('title', $productName ?: $heading)
@section('page-title', 'Scanned appliance')
@section('page-subtitle', $heading)

@section('content')
<div class="mx-auto max-w-lg space-y-4">
    <div class="flex gap-2">
        <a href="{{ route('admin.inventory.scan') }}" class="inline-flex flex-1 items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
            Scan again
        </a>
        <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex flex-1 items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
            Full record
        </a>
    </div>

    <section class="rounded-lg bg-white p-4 shadow">
        @if($productName)
        <h2 class="text-lg font-semibold text-gray-900">{{ $productName }}</h2>
        @endif
        <p class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusTones[$status] ?? 'border-gray-300 bg-gray-50 text-gray-900' }}">
            {{ $status }}
        </p>
        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3">
            @foreach($facts as $fact)
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $fact['label'] }}</dt>
                <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $fact['value'] ?: '—' }}</dd>
            </div>
            @endforeach
        </dl>
    </section>

    @if($workflow)
    <a href="{{ $workflow['url'] }}" class="flex min-h-12 items-center justify-center rounded-md bg-gray-900 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-800">
        {{ $workflow['label'] }}
    </a>
    @endif

    @canAccess('appliance.edit')
    <section class="rounded-lg bg-white p-4 shadow" x-data="{ status: @js(old('status', '')) }">
        <h3 class="text-sm font-semibold text-gray-900">Update status</h3>
        <form method="POST" action="{{ route('admin.inventory.status.update', $appliance) }}" class="mt-3 space-y-3">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" :value="status">
            <div class="grid grid-cols-2 gap-2">
                @foreach($statuses as $option)
                @php($isCurrent = $option === $status)
                <button type="button"
                        data-status="{{ $option }}"
                        @click="status = $el.dataset.status"
                        @disabled($isCurrent)
                        :class="status === $el.dataset.status ? 'ring-2 ring-gray-900 ring-offset-1' : ''"
                        class="min-h-12 rounded-md border px-3 py-2 text-left text-sm font-semibold leading-tight disabled:cursor-default disabled:opacity-60 {{ $statusTones[$option] ?? 'border-gray-300 bg-gray-50 text-gray-900' }}">
                    {{ $option }}
                    @if($isCurrent)
                    <span class="mt-0.5 block text-[11px] font-medium opacity-80">Current</span>
                    @endif
                </button>
                @endforeach
            </div>

            <div x-cloak x-show="status !== ''" class="space-y-3">
                <textarea name="notes" placeholder="Notes" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                <div x-cloak x-show="status === 'Sold'">
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="floor-sold-price">Sold price</label>
                    <input id="floor-sold-price" type="number" step="0.01" min="0" name="sold_price" :required="status === 'Sold'" value="{{ old('sold_price', $appliance->sold_price) }}" placeholder="Sold price (excl. taxes)" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
                <label x-cloak x-show="status === 'Holding for parts'" class="flex items-center gap-2 text-sm text-gray-800">
                    <input type="checkbox" name="parts_ordered" value="1" @checked(old('parts_ordered'))>
                    <span>Parts ordered</span>
                </label>
                <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                    Update status
                </button>
            </div>
        </form>
    </section>
    @endcanAccess
</div>
@endsection
