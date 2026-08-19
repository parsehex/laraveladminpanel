@extends('layouts.admin')

@section('title', 'Trucks')
@section('page-title', 'Trucks')

@section('content')
@php
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
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Trucks</h1>
        @canAccess('trucks.create')
        <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center" data-toggle-create>
            <i class="fas fa-plus mr-2"></i>Add truck
        </button>
        @endcanAccess
    </div>

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

    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.trucks.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request('direction'))
                <input type="hidden" name="direction" value="{{ request('direction') }}">
            @endif
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search by name</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="Truck name..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="breakdown" {{ request('status') === 'breakdown' ? 'selected' : '' }}>Breakdown</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Filter</button>
                <a href="{{ route('admin.trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Reset</a>
            </div>
        </form>
    </div>

    <x-admin.data-table id="truck-results" :table="$dataTable">
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
                        <x-admin.data-table.cell column="name" class="font-medium text-gray-900">{{ $truck->name }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="units">{{ $truck->units_on_truck }} (item:{{ $truck->appliances->count() }})</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="cost" align="right">${{ number_format($truck->cost_of_truck, 2) }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="shipping" align="right">${{ number_format((float) $truck->shipping_cost, 2) }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="total_msrp" align="right">${{ number_format($truck->total_appliance_msrp ?? 0, 2) }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="arrival">{{ $truck->arrival_date ? $truck->arrival_date : '-' }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="truck_status">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $truck->status === 'active' ? 'bg-green-100 text-green-800' : ($truck->status === 'breakdown' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst($truck->status) }}
                            </span>
                        </x-admin.data-table.cell>
                        <x-admin.data-table.cell column="status_breakdown" class="truck-status-breakdown-cell">
                            <div class="truck-status-breakdown">
                                @forelse($truck->appliance_statuses as $item)
                                    @php
                                        $status = $item['status'] ?: 'Triage';
                                        $count = $item['count'];
                                        $classes = $applianceStatusClasses[strtolower($status)] ?? 'status-white';
                                    @endphp
                                    <span class="status-chip {{ $classes }}">
                                        {{ ucfirst($status) }} ({{ $count }})
                                    </span>
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
                            <form method="POST" action="{{ route('admin.trucks.appliances.import', $truck) }}" enctype="multipart/form-data" class="inline" data-truck-import-form>
                                @csrf
                                <input type="file" name="csv_file" accept=".csv,text/csv" required class="hidden" data-truck-import-input>
                                <button type="button" class="text-indigo-600 hover:text-indigo-900" title="Import appliances" data-truck-import-button>
                                    <i class="fas fa-file-import"></i>
                                </button>
                            </form>
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
                        <td colspan="11" class="px-6 py-8 text-center text-gray-500">No trucks found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        @if($trucks->hasPages())
        <x-slot:footer>
        <div class="px-6 py-4 border-t border-gray-200">{{ $trucks->links() }}</div>
        </x-slot:footer>
        @endif
    </x-admin.data-table>
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

    $('[data-truck-import-button]').on('click', function () {
        $(this).closest('[data-truck-import-form]').find('[data-truck-import-input]').trigger('click');
    });

    $('[data-truck-import-input]').on('change', function () {
        if (this.files.length) {
            $(this).closest('form').trigger('submit');
        }
    });

    @if(request()->hasAny(['search', 'status']))
        setTimeout(function () {
            document.getElementById('truck-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
    @endif
</script>
@endpush
