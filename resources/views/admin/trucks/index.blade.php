@extends('layouts.admin')

@section('title', 'Trucks')
@section('page-title', 'Trucks')

@section('page-actions')
    @canAccess('trucks.create')
    <x-admin.csv-import-trigger
        :action="route('admin.trucks.import')"
        modal-title="Review truck import"
        modal-id="trucks-import-modal"
        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
    >
        <i class="fas fa-file-import mr-2"></i>Review truck import
    </x-admin.csv-import-trigger>
    <button type="button" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700" data-toggle-create>
        <i class="fas fa-plus mr-2"></i>Add truck
    </button>
    @endcanAccess
@endsection

@section('content')
@php
    $selectedItemStatuses = collect(request('item_status', []))->filter()->values()->all();
    $applianceStatusClasses = [
        'triage' => 'status-white',
        '' => 'status-white',
        'testing' => 'status-light-blue',
        'ready' => 'status-blue',
        'show room' => 'status-purple',
        'holding' => 'status-pink',
        'repair' => 'status-orange',
        'holding for parts' => 'status-yellow',
        'cleaning' => 'status-brown',
        'demanufacture' => 'status-red',
        'scrap' => 'status-black',
        'quality control qc' => 'status-green',
        'sold' => 'status-sold',
        'breakdown' => 'status-red',
    ];
@endphp
<div class="space-y-6">
    @canAccess('trucks.create')
    <div id="create-truck-panel" class="bg-white rounded-lg shadow p-6 {{ $errors->any() && old('_form') === 'create' ? '' : 'hidden' }}">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">New truck</h2>
            <button type="button" class="text-gray-600 hover:text-gray-900 text-sm" data-toggle-create>
                <i class="fas fa-times mr-1"></i>Close
            </button>
        </div>

        <form method="POST" action="{{ route('admin.trucks.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_form" value="create">
            @include('admin.trucks.form', ['truck' => null])

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600" data-toggle-create>Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
        </form>
    </div>
    @endcanAccess

    <div id="truck-breakdown-card" class="bg-white rounded-lg shadow overflow-hidden">
        <button type="button" data-toggle-breakdown class="w-full bg-green-600 text-white px-6 py-4 flex items-center justify-between text-left">
            <span class="font-semibold"><i class="fas fa-th mr-2"></i>Truck Inventory Breakdown</span>
            <i class="fas fa-chevron-down"></i>
        </button>
        <div id="truck-breakdown-panel" class="{{ $costRange->isSelected() ? '' : 'hidden' }}">
            <x-admin.cost-range-filter :action="route('admin.trucks.index')" :range="$costRange" />
            <div class="truck-breakdown-scroll">
            <table class="min-w-full divide-y divide-gray-200 truck-breakdown-table">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="truck-breakdown-sticky px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-50">Truck</th>
                        @foreach($breakdownStatuses as $breakdownStatus)
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ $breakdownStatus }}</th>
                        @endforeach
                        @if($showAdminValue)
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap border-l border-gray-200">Active Units</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Active Base Cost</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Parts Cost</th>
                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Active Value</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($breakdownRows as $breakdownRow)
                    <tr class="hover:bg-gray-50">
                        <td class="truck-breakdown-sticky px-4 py-2 text-sm font-medium whitespace-nowrap bg-white">
                            <a href="{{ route('admin.trucks.show', $breakdownRow['truck']) }}" class="text-blue-600 hover:text-blue-900">{{ $breakdownRow['truck']->name }}</a>
                        </td>
                        @foreach($breakdownStatuses as $breakdownStatus)
                        <td class="px-3 py-2 text-sm text-center {{ $breakdownRow['counts'][$breakdownStatus] ? 'text-gray-900 font-semibold' : 'text-gray-300' }}">
                            {{ $breakdownRow['counts'][$breakdownStatus] ?: '-' }}
                        </td>
                        @endforeach
                        @if($showAdminValue)
                        <td class="px-3 py-2 text-sm text-center text-gray-700 border-l border-gray-200">{{ $breakdownRow['active_units'] }}</td>
                        <td class="px-3 py-2 text-sm text-right text-gray-700 whitespace-nowrap">${{ number_format($breakdownRow['active_base_cost'], 2) }}</td>
                        <td class="px-3 py-2 text-sm text-right text-gray-700 whitespace-nowrap">${{ number_format($breakdownRow['active_parts_cost'], 2) }}</td>
                        <td class="px-3 py-2 text-sm text-right font-semibold text-gray-900 whitespace-nowrap">${{ number_format($breakdownRow['active_value'], 2) }}</td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 1 + count($breakdownStatuses) + ($showAdminValue ? 4 : 0) }}" class="px-6 py-6 text-center text-gray-500">{{ $costRange->from || $costRange->to ? 'No units added in this range.' : 'No trucks found.' }}</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($breakdownRows))
                <tfoot class="bg-gray-50">
                    <tr class="font-semibold text-gray-900">
                        <td class="truck-breakdown-sticky px-4 py-3 text-sm bg-gray-50">Totals</td>
                        @foreach($breakdownStatuses as $breakdownStatus)
                        <td class="px-3 py-3 text-sm text-center">{{ $breakdownTotals['counts'][$breakdownStatus] }}</td>
                        @endforeach
                        @if($showAdminValue)
                        <td class="px-3 py-3 text-sm text-center border-l border-gray-200">{{ $breakdownTotals['active_units'] }}</td>
                        <td class="px-3 py-3 text-sm text-right whitespace-nowrap">${{ number_format($breakdownTotals['active_base_cost'], 2) }}</td>
                        <td class="px-3 py-3 text-sm text-right whitespace-nowrap">${{ number_format($breakdownTotals['active_parts_cost'], 2) }}</td>
                        <td class="px-3 py-3 text-right text-lg font-bold text-green-700 whitespace-nowrap">${{ number_format($breakdownTotals['active_value'], 2) }}</td>
                        @endif
                    </tr>
                </tfoot>
                @endif
            </table>
            </div>
        </div>
    </div>

    <x-admin.filter-bar
        :action="route('admin.trucks.index')"
        form-class="grid grid-cols-1 md:grid-cols-6 gap-4"
        actions-class="flex items-end gap-2"
    >
        <x-admin.filter-field label="Search by name" for="search" class="md:col-span-2">
            <input type="text" id="search" name="search" value="{{ request('search') }}"
                   placeholder="Truck name..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </x-admin.filter-field>
        <x-admin.filter-field label="Truck status" for="status">
            <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="breakdown" @selected(request('status') === 'breakdown')>Breakdown</option>
            </select>
        </x-admin.filter-field>
        <x-admin.status-multiselect
            class="md:col-span-2"
            label="Item status"
            name="item_status[]"
            :options="$statuses"
            :selected="$selectedItemStatuses"
        />
    </x-admin.filter-bar>

    <x-admin.data-table id="truck-results" density="sheet" :table="$dataTable">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky-table-head">
                    <tr>
                        <x-admin.data-table.header-cells :data-table="$dataTable" :sort="$sort" :direction="$direction" />
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($trucks as $truck)
                    <tr class="hover:bg-gray-50">
                        <x-admin.data-table.cell column="name" class="font-medium text-gray-900" title="{{ $truck->name }}">{{ $truck->name }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="units">{{ $truck->units_on_truck }} (item:{{ $truck->appliances->count() }})</x-admin.data-table.cell>
                        @php
                            $totalCost = (float) $truck->cost_of_truck + (float) $truck->shipping_cost;
                            $includesShipping = (float) $truck->shipping_cost > 0;
                        @endphp
                        <x-admin.data-table.cell column="cost" align="right">
                            <span class="{{ $includesShipping ? 'rounded bg-amber-100 px-1 font-semibold text-amber-900' : '' }}"
                                  @if($includesShipping) title="Includes ${{ number_format((float) $truck->shipping_cost, 2) }} shipping" @endif>
                                ${{ number_format($totalCost, 2) }}
                            </span>
                        </x-admin.data-table.cell>
                        <x-admin.data-table.cell column="total_msrp" align="right">${{ number_format($truck->total_appliance_msrp ?? 0, 2) }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="arrival">{{ $truck->arrival_date ? $truck->arrival_date->format('m/d/y') : '-' }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="truck_status">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $truck->status === 'active' ? 'bg-green-100 text-green-800' : ($truck->status === 'breakdown' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst($truck->status) }}
                            </span>
                        </x-admin.data-table.cell>
                        <x-admin.data-table.cell column="status_breakdown" class="truck-status-breakdown-cell" title="{{ collect($truck->appliance_statuses)->map(fn ($item) => ucfirst($item['status'] ?: 'Triage').' ('.$item['count'].')')->implode(', ') ?: 'N/A' }}">
                            <div class="truck-status-breakdown">
                                @forelse($truck->appliance_statuses as $item)
                                    @php
                                        $status = $item['status'] ?: 'Triage';
                                        $count = $item['count'];
                                        $classes = $applianceStatusClasses[strtolower($status)] ?? 'status-white';
                                        $isSelected = in_array($status, $selectedItemStatuses, true);
                                        $nextItemStatuses = $isSelected
                                            ? array_values(array_filter($selectedItemStatuses, fn ($selected) => $selected !== $status))
                                            : [...$selectedItemStatuses, $status];
                                        $chipQuery = array_filter([
                                            'search' => request('search'),
                                            'status' => request('status'),
                                            'sort' => request('sort'),
                                            'direction' => request('direction'),
                                            'item_status' => $nextItemStatuses ?: null,
                                        ], fn ($value) => $value !== null && $value !== '');
                                    @endphp
                                    <a href="{{ route('admin.trucks.index', $chipQuery) }}"
                                       class="status-chip status-chip-filter {{ $classes }} {{ $isSelected ? 'is-selected' : '' }}"
                                       title="{{ $isSelected ? 'Remove '.$status.' filter' : 'Filter by '.$status }}">
                                        {{ ucfirst($status) }} ({{ $count }})
                                    </a>
                                @empty
                                    <span class="text-gray-500 text-sm">N/A</span>
                                @endforelse
                            </div>
                        </x-admin.data-table.cell>
                        <x-admin.data-table.cell column="revenue" align="right">${{ number_format((float) ($truck->revenue_to_date ?? 0), 2) }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="created_by" truncate title="{{ $truck->creator?->name ?? '-' }}">{{ $truck->creator?->name ?? '-' }}</x-admin.data-table.cell>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            @canAccess('trucks.view')
                            <a href="{{ route('admin.trucks.show', $truck) }}" class="text-blue-600 hover:text-blue-900" title="View"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.trucks.appliances.export', $truck) }}" class="text-emerald-600 hover:text-emerald-900" title="Export appliances">
                                <i class="fas fa-file-export"></i>
                            </a>
                            @endcanAccess
                            @canAccess('appliance.create')
                            <x-admin.csv-import-trigger
                                :action="route('admin.trucks.appliances.import', $truck)"
                                :modal-title="'Review appliance import for '.$truck->name"
                                class="text-indigo-600 hover:text-indigo-900"
                                title="Review appliance import"
                            >
                                <i class="fas fa-file-import"></i>
                            </x-admin.csv-import-trigger>
                            @endcanAccess
                            @canAccess('trucks.edit')
                            <a href="{{ route('admin.trucks.edit', $truck) }}" class="text-green-600 hover:text-green-900" title="Edit"><i class="fas fa-edit"></i></a>
                            @endcanAccess
                            @canAccess('trucks.delete')
                            <form action="{{ route('admin.trucks.destroy', $truck) }}" method="POST" class="inline" onsubmit="return confirm('Delete this truck?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            @endcanAccess
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500">No trucks found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        <x-slot:footer>
        <x-admin.table-pagination :paginator="$trucks" />
        </x-slot:footer>
    </x-admin.data-table>

    @canAccess('trucks.create')
    <x-admin.csv-import-modal
        id="trucks-import-modal"
        :example-url="asset('examples/trucks-import-example.csv')"
        submit-label="Review import"
        description="Upload a CSV to preview add/update changes for trucks. Rows with a matching name will be updated."
    />
    @endcanAccess

    @canAccess('appliance.create')
    <x-admin.csv-import-modal
        :example-url="asset('examples/truck-appliances-import-example.csv')"
        submit-label="Review import"
        description="Upload a CSV to preview add/update changes for appliances on the selected truck. Rows match by unit label first, then serial number. Optional sold columns: Sold Price, Sold By, Sold Date (or set Status to Sold)."
    />
    @endcanAccess
</div>
@endsection

@push('styles')
<style>
    .status-chip {
        display: inline-flex;
        align-items: center;
        flex: 0 0 auto;
        border: 0;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        min-height: 28px;
        white-space: nowrap;
    }

    a.status-chip-filter {
        text-decoration: none;
        cursor: pointer;
        transition: box-shadow 0.15s ease, transform 0.15s ease;
    }

    a.status-chip-filter:hover {
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.45);
        transform: translateY(-1px);
    }

    a.status-chip-filter.is-selected {
        box-shadow: 0 0 0 2px #2563eb;
    }

    .truck-status-breakdown-cell {
        min-width: 34rem;
        width: 34rem;
        max-width: 34rem;
    }

    .truck-status-breakdown {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem 0.75rem;
        max-width: 100%;
    }

    .truck-status-breakdown .status-chip {
        justify-content: flex-start;
    }

    .status-white { background: #e5e7eb !important; color: #111827 !important; }
    .status-light-blue { background: #d8f3ff !important; color: #111827 !important; }
    .status-blue { background: #cfe3ff !important; color: #111827 !important; }
    .status-purple { background: #dcfce7 !important; color: #111827 !important; }
    .status-pink { background: #fecdd3 !important; color: #111827 !important; }
    .status-orange { background: #fed7aa !important; color: #111827 !important; }
    .status-yellow { background: #fef3c7 !important; color: #111827 !important; }
    .status-brown { background: #fde68a !important; color: #111827 !important; }
    .status-red { background: #fecdd3 !important; color: #111827 !important; }
    .status-black { background: #d1d5db !important; color: #111827 !important; }
    .status-green { background: #dcfce7 !important; color: #111827 !important; }
    .status-sold { background: #cffafe !important; color: #111827 !important; }

    .truck-breakdown-scroll {
        max-height: 72vh;
        overflow: auto;
    }

    .truck-breakdown-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .truck-breakdown-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f9fafb;
        box-shadow: inset 0 -1px 0 #e5e7eb;
    }

    .truck-breakdown-sticky {
        position: sticky;
        left: 0;
        z-index: 1;
        box-shadow: inset -1px 0 0 #e5e7eb;
    }

    .truck-breakdown-table thead th.truck-breakdown-sticky {
        z-index: 3;
        box-shadow: inset -1px 0 0 #e5e7eb, inset 0 -1px 0 #e5e7eb;
    }
</style>
@endpush

@push('scripts')
<script>
    $('[data-toggle-create]').on('click', function () {
        const $panel = $('#create-truck-panel').toggleClass('hidden');
        if (! $panel.hasClass('hidden')) {
            $panel[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            $panel.find('input, select, textarea').filter(':visible:first').trigger('focus');
        }
    });

    $('[data-toggle-breakdown]').on('click', function () {
        $('#truck-breakdown-panel').toggleClass('hidden');
        window.pinTruckBreakdownHeader?.();
    });

    (function () {
        const main = document.querySelector('main');
        const scroller = document.querySelector('.truck-breakdown-scroll');

        if (! main || ! scroller) {
            return;
        }

        let frame = null;

        window.pinTruckBreakdownHeader = function () {
            frame = null;
            const headerCells = scroller.querySelectorAll('thead th');
            const header = headerCells[0];

            if (! header) {
                return;
            }

            const mainTop = main.getBoundingClientRect().top;
            const box = scroller.getBoundingClientRect();
            const headerHeight = header.offsetHeight;
            let shift = 0;

            if (box.top < mainTop && box.height > headerHeight) {
                shift = Math.min(mainTop - box.top, box.height - headerHeight);
            }

            headerCells.forEach((cell) => {
                cell.style.top = shift > 0 ? shift + 'px' : '';
            });
        };

        function schedulePin() {
            if (frame) {
                return;
            }

            frame = requestAnimationFrame(window.pinTruckBreakdownHeader);
        }

        main.addEventListener('scroll', schedulePin, { passive: true });
        window.addEventListener('resize', schedulePin);
        schedulePin();
    })();

    @if($costRange->isSelected())
        setTimeout(function () {
            document.getElementById('truck-breakdown-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
    @elseif(request()->hasAny(['search', 'status', 'item_status']))
        setTimeout(function () {
            document.getElementById('truck-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
    @endif
</script>
@endpush
