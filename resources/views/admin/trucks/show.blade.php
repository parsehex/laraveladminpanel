@extends('layouts.admin')

@section('title', $truck->name)
@section('page-title', 'Truck details')

@section('content')
<div class="max-w-7xl mx-auto flex flex-col gap-6">
    <div id="truck-details" class="order-3 bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl font-semibold text-gray-900">{{ $truck->name }}</h2>
            <div class="flex flex-wrap gap-2">
                @canAccess('trucks.edit')
                <a href="{{ route('admin.trucks.edit', $truck) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                    <i class="fas fa-edit mr-1"></i>Edit
                </a>
                @endcanAccess
                <a href="{{ route('admin.trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm">Back to list</a>
            </div>
        </div>

        <dl class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <dt class="text-sm font-medium text-gray-500">Units on truck</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $truck->units_on_truck }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Cost of truck</dt>
                <dd class="mt-1 text-sm text-gray-900">${{ number_format($truck->cost_of_truck, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Arrival date</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $truck->arrival_date ? $truck->arrival_date->format('Y-m-d') : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $truck->status === 'active' ? 'bg-green-100 text-green-800' : ($truck->status === 'breakdown' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                        {{ ucfirst($truck->status) }}
                    </span>
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">Notes</dt>
                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ $truck->notes ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Created by</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $truck->creator?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Last updated by</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $truck->updater?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Created at</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $truck->created_at->format('M j, Y g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Updated at</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $truck->updated_at->format('M j, Y g:i A') }}</dd>
            </div>
        </dl>

        @canAccess('trucks.delete')
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <form action="{{ route('admin.trucks.destroy', $truck) }}" method="POST" onsubmit="return confirm('Delete this truck?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                    <i class="fas fa-trash mr-1"></i>Delete truck
                </button>
            </form>
        </div>
        @endcanAccess
    </div>

    @canAccess('appliance.create')
    <div id="add-appliance" class="order-1 bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Add New Appliance to Truck</h2>
        </div>
        <form method="POST" action="{{ route('admin.trucks.appliances.store', $truck) }}" class="p-6 space-y-6">
            @csrf
            <input type="hidden" name="_form" value="create-appliance">
            @include('admin.trucks.partials.appliance-form', [
                'truck' => $truck,
                'appliance' => null,
                'categories' => $categories,
                'models' => $models,
                'prefix' => 'create-appliance',
            ])

            <div class="flex justify-end gap-2">
                <button type="reset" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Reset</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Submit
                </button>
            </div>
        </form>
    </div>
    @endcanAccess

    <div id="truck-appliances" class="order-2 bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-white">Truck Appliances ({{ $truck->appliances->count() }} total)</h2>
            <div class="flex flex-wrap gap-2">
                @canAccess('trucks.view')
                <a href="{{ route('admin.trucks.appliances.export', $truck) }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100">
                    <i class="fas fa-file-export mr-1"></i>Export
                </a>
                @endcanAccess
                @canAccess('appliance.create')
                <form method="POST" action="{{ route('admin.trucks.appliances.import', $truck) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="file" name="csv_file" accept=".csv,text/csv" required class="max-w-52 rounded-md bg-white px-2 py-1 text-sm text-gray-800">
                    <button type="submit" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100">
                        <i class="fas fa-file-import mr-1"></i>Import
                    </button>
                    <a href="{{ asset('examples/truck-appliances-import-example.csv') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100" download>Example CSV</a>
                </form>
                @endcanAccess
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MSRP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receiving Condition</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Parts Cost</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($truck->appliances as $appliance)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->model?->model_number ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->serial_number ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->brand ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->product_name ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${{ number_format($appliance->msrp, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->receiving_condition ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            @php
                                $classes = match(strtolower($appliance->status)) {

                                    'triage' => 'bg-indigo-100 text-indigo-800',

                                    'testing' => 'bg-blue-100 text-blue-800',

                                    'repair' => 'bg-orange-100 text-orange-800',

                                    'breakdown' => 'bg-red-100 text-red-800',

                                    'demanufacture' => 'bg-pink-100 text-pink-800',

                                    'cleaning' => 'bg-cyan-100 text-cyan-800',

                                    'ready' => 'bg-green-100 text-green-800',

                                    'scrap' => 'bg-gray-200 text-gray-800',

                                    'show room' => 'bg-purple-100 text-purple-800',

                                    'sold' => 'bg-emerald-100 text-emerald-800',

                                    'holding for parts' => 'bg-yellow-100 text-yellow-800',

                                    'holding' => 'bg-amber-100 text-amber-800',

                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $classes }}">
                                    {{ ucfirst($appliance->status) }}
                                </span>
                            </dd>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${{ number_format($appliance->total_parts_cost, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            @canAccess('appliance.edit')
                            <button type="button" class="text-green-600 hover:text-green-900" title="Edit" data-toggle-row="appliance-edit-{{ $appliance->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcanAccess
                            @canAccess('appliance.delete')
                            <form action="{{ route('admin.trucks.appliances.destroy', [$truck, $appliance]) }}" method="POST" class="inline" onsubmit="return confirm('Remove this appliance from truck?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            @endcanAccess
                        </td>
                    </tr>
                    @canAccess('appliance.edit')
                    <tr id="appliance-edit-{{ $appliance->id }}" class="{{ $errors->any() && old('_form') === 'edit-appliance-'.$appliance->id ? '' : 'hidden' }} bg-gray-50">
                        <td colspan="10" class="px-4 py-4">
                            <form method="POST" action="{{ route('admin.trucks.appliances.update', [$truck, $appliance]) }}" class="space-y-6">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_form" value="edit-appliance-{{ $appliance->id }}">
                                @include('admin.trucks.partials.appliance-form', [
                                    'truck' => $truck,
                                    'appliance' => $appliance,
                                    'categories' => $categories->merge($appliance->category ? collect([$appliance->category]) : collect())->unique('id'),
                                    'models' => $models->merge($appliance->model ? collect([$appliance->model]) : collect())->unique('id'),
                                    'prefix' => 'edit-appliance-'.$appliance->id,
                                ])

                                <div class="flex justify-end gap-2">
                                    <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600" data-toggle-row="appliance-edit-{{ $appliance->id }}">Cancel</button>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        <i class="fas fa-save mr-2"></i>Update
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endcanAccess
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-8 text-center text-gray-500">No appliances assigned to this truck.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('admin.shared.ajax-dropdowns')
@endsection

@push('scripts')
<script>
    $('[data-toggle-row]').on('click', function () {
        const $row = $('#' + $(this).data('toggle-row')).toggleClass('hidden');

        if (! $row.hasClass('hidden')) {
            $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $row.find('input, select, textarea').filter(':visible:first').trigger('focus');
        }
    });

    const openEditForm = '{{ old('_form') }}';
    if (openEditForm && openEditForm.startsWith('edit-appliance-')) {
        const $row = $('#appliance-' + openEditForm);
        if ($row.length) {
            setTimeout(function () {
                $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                $row.find('input, select, textarea').filter(':visible:first').trigger('focus');
            }, 150);
        }
    }
</script>
@endpush
