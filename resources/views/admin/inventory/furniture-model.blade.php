@extends('layouts.admin')

@php
    $pageHeading = $productName ?: ($model->model_number ?: 'Furniture model');
    $statusFilterUrl = function (string $status) use ($model, $selectedStatuses): string {
        $nextStatuses = in_array($status, $selectedStatuses, true)
            ? array_values(array_filter($selectedStatuses, fn (string $selected) => $selected !== $status))
            : [...$selectedStatuses, $status];

        return route('admin.inventory.furniture.show', [
            'model' => $model,
            ...collect(request()->query())->except(['status', 'page'])->all(),
            'status' => $nextStatuses ?: null,
        ]);
    };
    $facts = [
        ['label' => 'Model #', 'value' => $model->model_number],
        ['label' => 'Brand', 'value' => $brand],
        ['label' => 'Category', 'value' => $model->category?->name],
        ['label' => 'MSRP', 'value' => $msrp > 0 ? '$'.number_format($msrp, 2) : null],
        ['label' => 'Assembled', 'value' => $assembledCount.' of '.$unitCount],
    ];
@endphp

@section('title', $pageHeading)
@section('page-title', 'Furniture Model')
@section('page-subtitle', trim(implode(' ', array_filter([$brand, $model->model_number]))))

@section('page-actions')
    <a href="{{ route('admin.inventory.furniture') }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
        Back to furniture
    </a>
@endsection

@section('content')
<div class="space-y-6">
    <section class="rounded-lg bg-white p-6 shadow">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-xl font-bold text-gray-900">{{ $pageHeading }}</h2>
                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    @foreach($facts as $fact)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $fact['label'] }}</dt>
                        <dd class="font-medium text-gray-900">{{ $fact['value'] ?: '—' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>
            <div class="text-right">
                <div class="text-4xl font-extrabold leading-none text-gray-900">{{ $unitCount }}</div>
                <div class="mt-1 text-sm font-medium text-gray-500">{{ \Illuminate\Support\Str::plural('unit', $unitCount) }} in inventory</div>
            </div>
        </div>
        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4">
            @foreach($statusCounts as $statusCount)
            <x-admin.status-chip
                :status="$statusCount['status']"
                :count="$statusCount['count']"
                :href="$statusFilterUrl($statusCount['status'])"
                :selected="in_array($statusCount['status'], $selectedStatuses, true)"
            />
            @endforeach
            @if($selectedStatuses !== [])
            <a href="{{ route('admin.inventory.furniture.show', $model) }}" class="text-sm font-medium text-blue-700 hover:underline">Clear filter</a>
            @endif
        </div>
    </section>

    <section class="overflow-hidden rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="font-semibold text-gray-900"><i class="fas fa-truck mr-2 text-gray-500"></i>By truck</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Truck</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Units</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Statuses</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($truckBreakdown as $truckRow)
                    <tr>
                        <td class="px-6 py-3 text-sm">
                            <a href="{{ route('admin.trucks.show', $truckRow['truck_id']) }}" class="font-semibold text-blue-700 hover:underline">{{ $truckRow['truck_name'] }}</a>
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ $truckRow['count'] }}</td>
                        <td class="px-6 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($truckRow['statuses'] as $statusCount)
                                <x-admin.status-chip :status="$statusCount['status']" :count="$statusCount['count']" />
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg bg-white shadow" x-data="{ selected: [] }">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <h3 class="font-semibold text-gray-900">
                <i class="fas fa-couch mr-2 text-gray-500"></i>Units
                @if($selectedStatuses !== [])
                <span class="ml-1 text-sm font-normal text-gray-500">({{ count($shownUnitIds) }} matching {{ implode(', ', $selectedStatuses) }})</span>
                @endif
            </h3>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        x-bind:disabled="selected.length === 0"
                        x-on:click="window.open(@js(route('admin.inventory.stickers')) + '?ids=' + selected.join(','), '_blank')"
                        class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="fas fa-qrcode mr-2"></i>Print selected stickers <span class="ml-1" x-show="selected.length" x-text="'(' + selected.length + ')'"></span>
                </button>
                <a href="{{ route('admin.inventory.stickers', ['ids' => implode(',', $shownUnitIds)]) }}"
                   target="_blank"
                   class="inline-flex items-center justify-center rounded-md bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-teal-700">
                    <i class="fas fa-tags mr-2"></i>Print all {{ count($shownUnitIds) }} stickers
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox"
                                   aria-label="Select all units on this page"
                                   x-on:change="const pageIds = [...$root.querySelectorAll('[data-unit-checkbox]')].map((box) => box.value); selected = $event.target.checked ? [...new Set([...selected, ...pageIds])] : selected.filter((id) => ! pageIds.includes(id))">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit Label</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Truck</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Condition</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Assembled</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Received</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($units as $unit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input type="checkbox" data-unit-checkbox value="{{ $unit->id }}" x-model="selected" aria-label="Select {{ $unit->unit_label ?: 'unit '.$unit->id }}">
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <a href="{{ route('admin.inventory.show', $unit) }}" class="font-semibold text-blue-700 hover:underline">{{ $unit->unit_label ?: 'Unit #'.$unit->id }}</a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $unit->truck?->name ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3"><x-admin.status-chip :status="$unit->status" /></td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $unit->location ?: '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $unit->receiving_condition ?: '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $unit->furnitureDetails?->isAssembled() ? 'Yes' : 'No' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700" title="{{ $unit->created_at?->format('Y-m-d H:i:s') }}">{{ $unit->created_at?->format('m/d/y') ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.inventory.show', $unit) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100" title="View details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.inventory.stickers', ['ids' => $unit->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100" title="Print sticker">
                                <i class="fas fa-qrcode"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">No units match this filter.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-admin.table-pagination :paginator="$units" />
    </section>
</div>
@endsection
