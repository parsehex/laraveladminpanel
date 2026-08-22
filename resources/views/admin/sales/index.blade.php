@extends('layouts.admin')

@section('title', 'Sales Tracking')
@section('page-title', 'Sales Tracking')

@section('content')
@php
    $statusClasses = [
        'Ready' => 'appliance-status-blue',
        'Show Room' => 'appliance-status-purple',
        'Sold' => 'appliance-status-sold',
    ];
@endphp
<div class="space-y-6">
    @canAccess('sales.create')
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Mark Appliance as Sold</h2>
        </div>
        <div class="p-6">
            <div class="mb-4 flex gap-6">
                <label class="inline-flex items-center gap-2"><input type="radio" name="sale_form_type" value="normal" checked> Normal Sale</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="sale_form_type" value="custom"> Custom Sale</label>
            </div>

            <form method="POST" action="{{ route('admin.sales.mark-sold') }}" id="normal-sale-form" class="space-y-4">
                @csrf
                <input type="hidden" name="sale_type" value="normal">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Scan Serial Number</label>
                    <input type="text" name="serial_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sold Price (excl. taxes)</label>
                    <input type="number" step="0.01" name="sold_price" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Mark Sold</button>
            </form>

            <form method="POST" action="{{ route('admin.sales.mark-sold') }}" id="custom-sale-form" class="hidden space-y-4">
                @csrf
                <input type="hidden" name="sale_type" value="custom">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model No #</label>
                        <input type="text" name="model_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Scan Serial Number</label>
                        <input type="text" name="serial_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sold Price</label>
                        <input type="number" step="0.01" name="sold_price" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Price</label>
                        <input type="number" step="0.01" name="estimated_price" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Mark Sold</button>
            </form>
        </div>
    </div>
    @endcanAccess

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow overflow-hidden"><div class="bg-green-600 text-white px-4 py-3 font-semibold">Total Sales</div><div class="p-6 text-center text-3xl font-bold">${{ number_format($totalSales, 2) }}</div></div>
        <div class="bg-white rounded-lg shadow overflow-hidden"><div class="bg-yellow-500 text-white px-4 py-3 font-semibold">Total Cost</div><div class="p-6 text-center text-3xl font-bold">${{ number_format($totalCost, 2) }}</div></div>
        <div class="bg-white rounded-lg shadow overflow-hidden"><div class="bg-cyan-600 text-white px-4 py-3 font-semibold">Profit</div><div class="p-6 text-center text-3xl font-bold">${{ number_format($totalProfit, 2) }}</div></div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-4 pt-4">
            <nav class="-mb-px flex gap-2" aria-label="Sales tabs">
                <a href="{{ route('admin.sales.index', array_merge(request()->except('page'), ['view' => 'normal'])) }}" class="inline-flex items-center rounded-t-lg border px-4 py-2 text-sm font-semibold {{ $view === 'normal' ? 'border-blue-200 border-b-white bg-white text-blue-700 shadow-sm' : 'border-transparent bg-gray-100 text-gray-600 hover:bg-white hover:text-gray-900' }}">
                    <i class="fas fa-cash-register mr-2"></i>Normal Sales
                </a>
                <a href="{{ route('admin.sales.index', array_merge(request()->except('page'), ['view' => 'custom'])) }}" class="inline-flex items-center rounded-t-lg border px-4 py-2 text-sm font-semibold {{ $view === 'custom' ? 'border-blue-200 border-b-white bg-white text-blue-700 shadow-sm' : 'border-transparent bg-gray-100 text-gray-600 hover:bg-white hover:text-gray-900' }}">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>Custom Sales
                </a>
            </nav>
        </div>
        <div class="bg-blue-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">{{ $view === 'normal' ? 'Ready / Showroom / Sold' : 'Sold Items' }}</h2>
        </div>
        <div class="p-4">
            <form method="GET" action="{{ route('admin.sales.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-4">
                <input type="hidden" name="view" value="{{ $view }}">
                @if($view === 'normal' && request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                @if($view === 'normal' && request('direction'))
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                @endif
                <input type="text" name="search" value="{{ request('search') }}" class="md:col-span-4 px-3 py-2 border border-gray-300 rounded-md" placeholder="{{ $view === 'normal' ? 'Search model, serial, location...' : 'Search model or serial' }}">
                @if($view === 'normal')
                <div class="md:col-span-6 flex flex-wrap items-center gap-2">
                    @foreach($trackingStatuses as $status)
                        @php
                            $count = (int) ($statusCounts[$status] ?? 0);
                            $statusClass = $statusClasses[$status] ?? '';
                        @endphp
                        <label class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 shadow-sm">
                            <input type="checkbox" name="status[]" value="{{ $status }}" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(in_array($status, $selectedStatuses, true))>
                            <span class="appliance-status-chip {{ $statusClass }}">{{ $status }}</span>
                            <span class="text-gray-500">{{ $count }}</span>
                        </label>
                    @endforeach
                </div>
                @endif
                <div class="md:col-span-2 flex gap-2">
                    <button class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md">Filter</button>
                    <a href="{{ route('admin.sales.index', ['view' => $view]) }}" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-md text-center">Reset</a>
                </div>
            </form>

            @if($view === 'normal')
            <x-admin.data-table bare :table="$dataTable">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky-table-head">
                    <tr>
                        <x-admin.data-table.header-cells :data-table="$dataTable" :sort="$sort" :direction="$direction" />
                        <th class="sticky-action px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($units as $item)
                        @php
                            $status = $item->status ?: 'Ready';
                            $isSold = $status === 'Sold';
                            $cost = $item->salesCost();
                            $profit = $isSold ? (float) ($item->sold_price ?? 0) - $cost : null;
                            $statusClass = $statusClasses[$status] ?? 'appliance-status-blue';
                        @endphp
                        <tr class="appliance-status-row {{ $statusClass }}">
                            <x-admin.data-table.cell column="id">{{ $item->id }}</x-admin.data-table.cell>
                            <x-admin.data-table.cell column="status">
                                <span class="appliance-status-chip {{ $statusClass }}">{{ $status }}</span>
                            </x-admin.data-table.cell>
                            <x-admin.data-table.cell column="location" truncate title="{{ $item->location ?: '—' }}">{{ $item->location ?: '—' }}</x-admin.data-table.cell>
                            <x-admin.data-table.cell column="model">{{ $item->model?->model_number ?? '-' }}</x-admin.data-table.cell>
                            <x-admin.data-table.cell column="serial_number">{{ $item->serial_number }}</x-admin.data-table.cell>
                            <x-admin.data-table.cell column="sold_price" align="right">{{ $isSold ? '$'.number_format($item->sold_price ?? 0, 2) : '—' }}</x-admin.data-table.cell>
                            <x-admin.data-table.cell column="cost" align="right">${{ number_format($cost, 2) }}</x-admin.data-table.cell>
                            <x-admin.data-table.cell column="profit" align="right">{{ $profit === null ? '—' : '$'.number_format($profit, 2) }}</x-admin.data-table.cell>
                            <x-admin.data-table.cell column="sold_by" truncate title="{{ $item->sold_by ?: '-' }}">{{ $isSold ? ($item->sold_by ?: '-') : '—' }}</x-admin.data-table.cell>
                            <x-admin.data-table.cell column="sold_date">{{ $isSold ? ($item->sold_at?->format('Y-m-d H:i') ?: '-') : '—' }}</x-admin.data-table.cell>
                            <td class="sticky-action px-4 py-3 text-right">
                                <a href="{{ route('admin.inventory.show', $item) }}" class="text-blue-600">View</a>
                                @if($isSold)
                                    @canAccess('sales.edit')
                                    <button class="text-yellow-600 ml-2" data-edit-sale data-action="{{ route('admin.sales.sold-price.update', $item) }}" data-price="{{ $item->sold_price }}" data-sold-by="{{ $item->sold_by }}">Edit Price</button>
                                    @endcanAccess
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="px-4 py-6 text-center text-gray-500">No matching units.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            <x-slot:footer>
            <x-admin.table-pagination :paginator="$units" />
            </x-slot:footer>
            </x-admin.data-table>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">ID</th><th class="px-4 py-3 text-left">Model</th><th class="px-4 py-3 text-left">Serial</th><th class="px-4 py-3 text-right">Sold Price</th><th class="px-4 py-3 text-right">Estimated</th><th class="px-4 py-3 text-right">Profit</th><th class="px-4 py-3 text-left">Sold By</th><th class="px-4 py-3 text-left">Sold Date</th></tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($customSales as $sale)
                        <tr><td class="px-4 py-3">{{ $sale->id }}</td><td class="px-4 py-3">{{ $sale->model_number }}</td><td class="px-4 py-3">{{ $sale->serial_number }}</td><td class="px-4 py-3 text-right">${{ number_format($sale->sold_price, 2) }}</td><td class="px-4 py-3 text-right">${{ number_format($sale->estimated_price, 2) }}</td><td class="px-4 py-3 text-right">${{ number_format($sale->sold_price - $sale->estimated_price, 2) }}</td><td class="px-4 py-3">{{ $sale->sold_by }}</td><td class="px-4 py-3">{{ $sale->created_at?->format('Y-m-d H:i') }}</td></tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No custom sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-admin.table-pagination :paginator="$customSales" />
            @endif
        </div>
    </div>
</div>

<div id="edit-sale-modal" class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center p-6">
    <div class="bg-white rounded-lg shadow max-w-md w-full p-6">
        <h3 class="text-lg font-semibold mb-4">Edit Sold Price</h3>
        <form method="POST" id="edit-sale-form" class="space-y-4">
            @csrf
            @method('PATCH')
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Sold Price</label><input type="number" step="0.01" name="sold_price" id="edit-sold-price" class="w-full px-3 py-2 border border-gray-300 rounded-md" required></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Sold By</label><input type="text" name="sold_by" id="edit-sold-by" class="w-full px-3 py-2 border border-gray-300 rounded-md" required></div>
            <div class="flex justify-end gap-2"><button type="button" id="close-edit-sale" class="px-4 py-2 bg-gray-500 text-white rounded-md">Cancel</button><button class="px-4 py-2 bg-blue-600 text-white rounded-md">Update Price</button></div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .appliance-status-row > td {
        color: #111827 !important;
        font-weight: 600;
        border-color: rgba(15, 23, 42, 0.12) !important;
    }

    .appliance-status-row:hover > td {
        filter: brightness(0.96);
    }

    .appliance-status-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid rgba(15, 23, 42, 0.2);
        border-radius: 999px;
        padding: 4px 9px;
        font-size: 11px;
        font-weight: 800;
        line-height: 1.1;
        min-height: 22px;
    }

    .appliance-status-row.appliance-status-blue > td { background: #93c5fd !important; }
    .appliance-status-row.appliance-status-purple > td { background: #d8b4fe !important; }
    .appliance-status-row.appliance-status-sold > td { background: #6ee7b7 !important; }

    .appliance-status-chip.appliance-status-blue { background: #3b82f6 !important; color: #ffffff !important; }
    .appliance-status-chip.appliance-status-purple { background: #9333ea !important; color: #ffffff !important; }
    .appliance-status-chip.appliance-status-sold { background: #059669 !important; color: #ffffff !important; }
</style>
@endpush

@push('scripts')
<script>
    $('input[name="sale_form_type"]').on('change', function () {
        const custom = $(this).val() === 'custom';
        $('#normal-sale-form').toggleClass('hidden', custom);
        $('#custom-sale-form').toggleClass('hidden', !custom);
    });

    $('[data-edit-sale]').on('click', function () {
        $('#edit-sale-form').attr('action', $(this).data('action'));
        $('#edit-sold-price').val($(this).data('price'));
        $('#edit-sold-by').val($(this).data('sold-by') || '');
        $('#edit-sale-modal').removeClass('hidden').addClass('flex');
    });

    $('#close-edit-sale, #edit-sale-modal').on('click', function (event) {
        if (event.target.id === 'close-edit-sale' || event.target.id === 'edit-sale-modal') {
            $('#edit-sale-modal').addClass('hidden').removeClass('flex');
        }
    });
</script>
@endpush
