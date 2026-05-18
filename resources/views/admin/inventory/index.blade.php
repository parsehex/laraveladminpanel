@extends('layouts.admin')

@section('title', 'Inventory')
@section('page-title', 'Inventory')

@section('content')
@php
    $selectedStatuses = collect(request('status', []))->filter()->values()->all();
    $statusClasses = [
        'Triage' => 'bg-slate-100 text-slate-800',
        'Testing' => 'bg-blue-100 text-blue-800',
        'Repair' => 'bg-amber-100 text-amber-800',
        'Demanufacture' => 'bg-orange-100 text-orange-800',
        'Cleaning' => 'bg-cyan-100 text-cyan-800',
        'Ready' => 'bg-green-100 text-green-800',
        'Scrap' => 'bg-red-100 text-red-800',
        'Show Room' => 'bg-purple-100 text-purple-800',
        'Sold' => 'bg-gray-200 text-gray-800',
        'Holding for parts' => 'bg-yellow-100 text-yellow-900',
        'Holding' => 'bg-yellow-100 text-yellow-900',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Inventory</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-print-selected class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
                <i class="fas fa-print mr-2"></i>Print selected
            </button>
            <button type="button" data-print-page class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
                <i class="fas fa-file-alt mr-2"></i>Print current page
            </button>
        </div>
    </div>

    @if($showAdminValue)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <button type="button" data-toggle-value class="w-full bg-green-600 text-white px-6 py-4 flex items-center justify-between text-left">
            <span class="font-semibold"><i class="fas fa-dollar-sign mr-2"></i>Active Inventory Cost Structure</span>
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="inventory-value-panel" class="hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Total Base Cost</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Parts Cost</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Inventory Value</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($inventoryData as $row)
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700">{{ $row->current_status }}</td>
                        <td class="px-6 py-3 text-sm text-gray-700 text-center">{{ $row->unit_count }}</td>
                        <td class="px-6 py-3 text-sm text-gray-700 text-right">${{ number_format($row->total_base_cost, 2) }}</td>
                        <td class="px-6 py-3 text-sm text-gray-700 text-right">${{ number_format($row->total_parts_cost, 2) }}</td>
                        <td class="px-6 py-3 text-sm font-semibold text-gray-900 text-right">${{ number_format($row->total_inventory_value, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">No active inventory.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="4" class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Active Value Grand Total:</td>
                        <td class="px-6 py-3 text-right text-lg font-bold text-green-700">${{ number_format($totalInventoryValue, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-3">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Model, serial, product..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="lg:col-span-2">
                <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                <select id="brand" name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All brands</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select id="category_id" name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-2">
                <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Rows</label>
                <select id="limit" name="limit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach([25, 50, 100, 250, 500, 1000] as $option)
                    <option value="{{ $option }}" @selected((string) request('limit', 25) === (string) $option)>{{ $option }}</option>
                    @endforeach
                    <option value="all" @selected(request('limit') === 'all')>All</option>
                </select>
            </div>
            <div class="lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <div class="relative" data-status-filter>
                    <button type="button" class="w-full px-3 py-2 border border-gray-300 rounded-md text-left bg-white flex items-center justify-between">
                        <span>{{ count($selectedStatuses) ? count($selectedStatuses).' selected' : 'Select status' }}</span>
                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                    </button>
                    <div class="hidden absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-y-auto p-2">
                        @foreach($statuses as $status)
                        <label class="flex items-center gap-2 px-2 py-1 text-sm text-gray-700 hover:bg-gray-50 rounded">
                            <input type="checkbox" name="status[]" value="{{ $status }}" @checked(in_array($status, $selectedStatuses, true))>
                            <span>{{ $status }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="lg:col-span-12 flex flex-wrap justify-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Apply</button>
                <a href="{{ route('admin.inventory.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Clear</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Current Inventory List</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><input type="checkbox" data-select-all></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($items as $item)
                    @php
                        $status = $item->status ?: 'Triage';
                        $totalCost = (float) $item->msrp + (float) $item->total_parts_cost;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><input type="checkbox" name="print_ids[]" value="{{ $item->id }}"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->truck?->name ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->model?->model_number ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->serial_number ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->brand ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClasses[$status] ?? 'bg-gray-100 text-gray-800' }}">{{ $status }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 text-right">${{ number_format($totalCost, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.inventory.show', $item) }}" class="text-blue-600 hover:text-blue-900" title="View details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">No inventory found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="text-sm text-gray-600">
                Showing {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
            </div>
            @if($items->hasPages())
            <div>{{ $items->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $('[data-toggle-value]').on('click', function () {
        $('#inventory-value-panel').toggleClass('hidden');
    });

    $('[data-status-filter] > button').on('click', function (event) {
        event.stopPropagation();
        $(this).siblings('div').toggleClass('hidden');
    });

    $('[data-status-filter]').on('click', function (event) {
        event.stopPropagation();
    });

    $(document).on('click', function () {
        $('[data-status-filter] > div').addClass('hidden');
    });

    $('[data-select-all]').on('change', function () {
        $('input[name="print_ids[]"]').prop('checked', $(this).is(':checked'));
    });

    $('[data-print-selected]').on('click', function () {
        const ids = $('input[name="print_ids[]"]:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) {
            alert('Please select at least one appliance to print.');
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('print', '1');
        url.searchParams.set('ids', ids.join(','));
        window.open(url.toString(), '_blank');
    });

    $('[data-print-page]').on('click', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('print', '1');
        url.searchParams.delete('ids');
        window.open(url.toString(), '_blank');
    });
</script>
@endpush
