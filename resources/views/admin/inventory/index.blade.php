@extends('layouts.admin')

@section('title', 'Inventory')
@section('page-title', 'Inventory')

@section('content')
@php
    $selectedStatuses = collect(request('status', []))->filter()->values()->all();
    $statusClasses = [
        'Triage' => 'appliance-status-white',
        'Testing' => 'appliance-status-light-blue',
        'Repair' => 'appliance-status-orange',
        'Breakdown' => 'appliance-status-red',
        'Demanufacture' => 'appliance-status-red',
        'Cleaning' => 'appliance-status-brown',
        'Ready' => 'appliance-status-blue',
        'Scrap' => 'appliance-status-black',
        'Show Room' => 'appliance-status-purple',
        'Sold' => 'appliance-status-sold',
        'Holding for parts' => 'appliance-status-yellow',
        'Holding' => 'appliance-status-pink',
        'Quality Control QC' => 'appliance-status-green',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Inventory</h1>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-select-all-button class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
                <i class="fas fa-check-square mr-2"></i>Select all
            </button>
            <button type="button" data-unselect-all-button class="bg-slate-500 hover:bg-slate-600 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
                <i class="far fa-square mr-2"></i>Unselect all
            </button>
            <button type="button" data-print-selected class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
                <i class="fas fa-print mr-2"></i>Print selected
            </button>
            <button type="button" data-print-selected-stickers class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
                <i class="fas fa-qrcode mr-2"></i>Print selected stickers
            </button>
            <button type="button" data-print-page class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
                <i class="fas fa-file-alt mr-2"></i>Print current page
            </button>
            <button type="button" data-print-page-stickers class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
                <i class="fas fa-tags mr-2"></i>Print current page stickers
            </button>
        </div>
    </div>

    @if($showAdminValue)
    <div id="inventory-value-card" class="bg-white rounded-lg shadow overflow-hidden">
        <button type="button" data-toggle-value class="w-full bg-green-600 text-white px-6 py-4 flex items-center justify-between text-left">
            <span class="font-semibold"><i class="fas fa-dollar-sign mr-2"></i>Active Inventory Cost Structure</span>
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="inventory-value-panel" class="{{ request('cost_date') ? '' : 'hidden' }} overflow-x-auto">
            <div class="border-b border-blue-100 bg-blue-50 p-3">
                <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-wrap items-center gap-2" onclick="event.stopPropagation();">
                    @foreach(request()->except(['cost_date', 'page']) as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $nestedValue)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $nestedValue }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="cost_date" class="text-sm font-semibold text-gray-700">Date (EOD):</label>
                    <input type="date" id="cost_date" name="cost_date" value="{{ request('cost_date') }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Apply</button>
                    @if(request('cost_date'))
                    <span class="text-sm font-semibold text-blue-700">Showing End of Day snapshot for: {{ \Carbon\Carbon::parse(request('cost_date'))->format('m/d/Y') }}</span>
                    @endif
                </form>
            </div>
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
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="relative z-30 grid grid-cols-1 lg:grid-cols-12 gap-4">
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
                <label for="sub_category" class="block text-sm font-medium text-gray-700 mb-1">Sub Category</label>
                <select id="sub_category" name="sub_category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All sub categories</option>
                    @foreach($subcategories as $subcategory)
                    <option value="{{ $subcategory }}" @selected(request('sub_category') === $subcategory)>{{ $subcategory }}</option>
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
            <div class="lg:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <div class="relative z-[99999]" data-status-filter>
                    <button type="button" class="w-full min-h-[42px] px-3 py-2 border border-gray-300 rounded-md text-left bg-white text-gray-800 shadow-sm flex items-center justify-between gap-2 hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="truncate">{{ count($selectedStatuses) ? count($selectedStatuses).' selected' : 'Select status' }}</span>
                        <i class="fas fa-chevron-down text-xs text-blue-600"></i>
                    </button>
                    <div class="hidden absolute right-0 top-full z-[99999] mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-lg border border-gray-200 bg-white p-2 shadow-2xl ring-1 ring-black/5 max-h-72 overflow-y-auto">
                        @foreach($statuses as $status)
                        <label class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                            <input type="checkbox" name="status[]" value="{{ $status }}" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(in_array($status, $selectedStatuses, true))>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Label</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SubCategory</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Date/Time</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sold Price</th>
                        <th class="sticky-action px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($items as $item)
                    @php
                        $status = $item->status ?: 'Triage';
                        $totalCost = $item->totalCostUsing((float) $item->msrp);
                        $statusClass = $statusClasses[$status] ?? 'appliance-status-white';
                    @endphp
                    <tr class="appliance-status-row {{ $statusClass }}">
                        <td class="px-4 py-3"><input type="checkbox" name="print_ids[]" value="{{ $item->id }}"></td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->truck?->name ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->unit_label ?: 'N/A' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->model?->model_number ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->serial_number ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->brand ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->subcategory ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="appliance-status-chip {{ $statusClass }}">{{ $status }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->statusHistories?->sortByDesc('created_at')->first()?->created_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 text-right">${{ number_format($totalCost, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 text-right">${{ number_format((float) ($item->sold_price ?? 0), 2) }}</td>
                        <td class="sticky-action px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.inventory.show', $item) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100" title="View details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.inventory.stickers', ['ids' => $item->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100" title="Print sticker">
                                <i class="fas fa-qrcode"></i>
                            </a>
                            @if(auth()->user()?->hasRole('admin') || auth()->user()?->role === 'admin')
                            <form action="{{ route('admin.inventory.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this appliance from inventory? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-700 hover:bg-red-100" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="px-6 py-8 text-center text-gray-500">No inventory found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="text-sm text-gray-600">
                Showing {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} of {{ $items->total() }}
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" data-select-all-button class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-md text-sm inline-flex items-center justify-center">
                    <i class="fas fa-check-square mr-2"></i>Select all
                </button>
                <button type="button" data-unselect-all-button class="bg-slate-500 hover:bg-slate-600 text-white px-4 py-2 rounded-md text-sm inline-flex items-center justify-center">
                    <i class="far fa-square mr-2"></i>Unselect all
                </button>
            </div>
            @if($items->hasPages())
            <div>{{ $items->links() }}</div>
            @endif
        </div>
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

    .appliance-status-row.appliance-status-white > td { background: #ffffff !important; }
    .appliance-status-row.appliance-status-light-blue > td { background: #b8e7f7 !important; }
    .appliance-status-row.appliance-status-blue > td { background: #93c5fd !important; }
    .appliance-status-row.appliance-status-purple > td { background: #d8b4fe !important; }
    .appliance-status-row.appliance-status-pink > td { background: #f9a8d4 !important; }
    .appliance-status-row.appliance-status-orange > td { background: #fdba74 !important; }
    .appliance-status-row.appliance-status-yellow > td { background: #fde047 !important; }
    .appliance-status-row.appliance-status-brown > td { background: #c08457 !important; }
    .appliance-status-row.appliance-status-red > td { background: #fca5a5 !important; }
    .appliance-status-row.appliance-status-black > td { background: #374151 !important; color: #ffffff !important; }
    .appliance-status-row.appliance-status-green > td { background: #86efac !important; }
    .appliance-status-row.appliance-status-sold > td { background: #6ee7b7 !important; }

    .appliance-status-chip.appliance-status-white { background: #ffffff !important; color: #111827 !important; }
    .appliance-status-chip.appliance-status-light-blue { background: #67d4f1 !important; color: #083344 !important; }
    .appliance-status-chip.appliance-status-blue { background: #3b82f6 !important; color: #ffffff !important; }
    .appliance-status-chip.appliance-status-purple { background: #9333ea !important; color: #ffffff !important; }
    .appliance-status-chip.appliance-status-pink { background: #ec4899 !important; color: #ffffff !important; }
    .appliance-status-chip.appliance-status-orange { background: #f97316 !important; color: #111827 !important; }
    .appliance-status-chip.appliance-status-yellow { background: #eab308 !important; color: #111827 !important; }
    .appliance-status-chip.appliance-status-brown { background: #7c2d12 !important; color: #ffffff !important; }
    .appliance-status-chip.appliance-status-red { background: #dc2626 !important; color: #ffffff !important; }
    .appliance-status-chip.appliance-status-black { background: #111827 !important; color: #ffffff !important; }
    .appliance-status-chip.appliance-status-green { background: #16a34a !important; color: #052e16 !important; }
    .appliance-status-chip.appliance-status-sold { background: #059669 !important; color: #ffffff !important; }
</style>
@endpush

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

    $('[data-select-all-button]').on('click', function () {
        $('input[name="print_ids[]"]').prop('checked', true);
        $('[data-select-all]').prop('checked', true);
    });

    $('[data-unselect-all-button]').on('click', function () {
        $('input[name="print_ids[]"]').prop('checked', false);
        $('[data-select-all]').prop('checked', false);
    });

    @if(request('cost_date'))
    setTimeout(function () {
        const target = document.getElementById('inventory-value-card');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 150);
    @endif

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

    function selectedInventoryIds() {
        return $('input[name="print_ids[]"]:checked').map(function () {
            return $(this).val();
        }).get();
    }

    function currentPageInventoryIds() {
        return $('input[name="print_ids[]"]').map(function () {
            return $(this).val();
        }).get();
    }

    function openStickerPrint(ids) {
        if (!ids.length) {
            alert('Please select at least one appliance to print a sticker for.');
            return;
        }

        const url = new URL('{{ route('admin.inventory.stickers') }}');
        url.searchParams.set('ids', ids.join(','));
        window.open(url.toString(), '_blank');
    }

    $('[data-print-selected-stickers]').on('click', function () {
        openStickerPrint(selectedInventoryIds());
    });

    $('[data-print-page-stickers]').on('click', function () {
        openStickerPrint(currentPageInventoryIds());
    });
</script>
@endpush
