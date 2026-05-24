@extends('layouts.admin')

@section('title', $truck->name)
@section('page-title', 'Truck details')

@section('content')
@php
    $rowStatusClasses = [
        'triage' => 'bg-slate-50',
        'testing' => 'bg-blue-50',
        'repair' => 'bg-amber-50',
        'breakdown' => 'bg-red-50',
        'demanufacture' => 'bg-rose-100',
        'cleaning' => 'bg-cyan-50',
        'ready' => 'bg-green-50',
        'scrap' => 'bg-gray-200',
        'show room' => 'bg-purple-100',
        'sold' => 'bg-emerald-50',
        'holding for parts' => 'bg-yellow-50',
        'holding' => 'bg-yellow-50',
    ];
@endphp
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
            <h2 class="text-xl font-semibold text-white">Truck Appliances ({{ $appliances->total() }} total)</h2>
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

        <div class="border-b border-gray-200 bg-white px-4 py-3">
            <form method="GET" action="{{ route('admin.trucks.show', $truck) }}" class="flex flex-wrap items-center gap-2">
                <label for="appliances_per_page" class="text-sm font-medium text-gray-700">Rows per page:</label>
                <select id="appliances_per_page" name="appliances_per_page" onchange="this.form.submit()" class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
                <span class="ml-auto text-sm text-gray-600">
                    Showing {{ $appliances->firstItem() ?? 0 }} - {{ $appliances->lastItem() ?? 0 }} of {{ $appliances->total() }}
                </span>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><input type="checkbox" data-truck-select-all></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Label</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial #</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Our Cost</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MSRP</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fuel Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receiving Condition</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Parts Cost</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($appliances as $appliance)
                    @php($rowClass = $rowStatusClasses[strtolower($appliance->status ?: 'triage')] ?? 'bg-white')
                    <tr class="{{ $rowClass }} hover:brightness-[0.98]">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            <input type="checkbox" name="truck_print_ids[]" value="{{ $appliance->id }}" data-truck-appliance-checkbox>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->category?->name ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->unit_label ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->model?->model_number ?? '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->serial_number ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->brand ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->product_name ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->quantity ?? 1 }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${{ number_format((float) $appliance->price, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${{ number_format($appliance->msrp, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->fuel_type ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $appliance->receiving_condition ?: '-' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $rowClass }}">
                                    {{ ucfirst($appliance->status) }}
                                </span>
                            </dd>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${{ number_format($appliance->total_parts_cost, 2) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-1.5">
                            @canAccess('appliance.edit')
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Edit" data-toggle-row="appliance-edit-{{ $appliance->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcanAccess
                            @canAccess('inventory.view')
                            <button type="button" data-photo-modal-url="{{ route('admin.inventory.photos.index', $appliance) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-green-200 bg-green-50 text-green-700 hover:bg-green-100" title="View photos">
                                <i class="fas fa-images"></i>
                            </button>
                            <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100" title="View details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.inventory.stickers', ['ids' => $appliance->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100" title="Print sticker">
                                <i class="fas fa-qrcode"></i>
                            </a>
                            @endcanAccess
                            @canAccess('appliance.delete')
                            <form action="{{ route('admin.trucks.appliances.destroy', [$truck, $appliance]) }}" method="POST" class="inline" onsubmit="return confirm('Remove this appliance from truck?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-red-50 text-red-700 hover:bg-red-100" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            @endcanAccess
                            </div>
                        </td>
                    </tr>
                    @canAccess('appliance.edit')
                    <tr id="appliance-edit-{{ $appliance->id }}" class="{{ $errors->any() && old('_form') === 'edit-appliance-'.$appliance->id ? '' : 'hidden' }} bg-gray-50">
                        <td colspan="15" class="px-4 py-4">
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
                        <td colspan="15" class="px-6 py-8 text-center text-gray-500">No appliances assigned to this truck.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appliances->total() > 0)
        <div class="border-t border-gray-200 bg-white px-4 py-4">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700" data-truck-select-all-button>Select All</button>
                <button type="button" class="rounded-md bg-gray-500 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600" data-truck-reset-selection>Reset</button>
                <button type="button" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700" data-truck-print-selected-sheets><i class="fas fa-print mr-2"></i>Print Selected Sheet(s)</button>
                <button type="button" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" data-truck-print-selected-stickers><i class="fas fa-qrcode mr-2"></i>Print Selected Sticker(s)</button>
                <button type="button" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800" data-truck-print-all-sheets><i class="fas fa-file-alt mr-2"></i>Print All Sheets</button>
                <button type="button" class="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800" data-truck-print-all-stickers><i class="fas fa-tags mr-2"></i>Print All Stickers</button>
            </div>
        </div>
        @endif
        @if($appliances->hasPages())
        <div class="border-t border-gray-200 bg-white px-4 py-4">
            {{ $appliances->links() }}
        </div>
        @endif
    </div>
</div>

@include('admin.shared.ajax-dropdowns')
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .swal-photo-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
        max-height: 68vh;
        overflow-y: auto;
        padding: 2px;
    }

    .swal-photo-gallery button {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        border: 1px solid #d7dee8;
        border-radius: 8px;
        background: #f1f5f9;
        cursor: pointer;
        padding: 0;
    }

    .swal-photo-gallery img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.2s ease, filter 0.2s ease;
    }

    .swal-photo-gallery button:hover img {
        transform: scale(1.04);
        filter: brightness(0.86);
    }

    .swal-photo-gallery span {
        position: absolute;
        left: 8px;
        bottom: 8px;
        border-radius: 6px;
        background: rgba(15, 23, 42, 0.72);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 8px;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .swal-photo-gallery button:hover span {
        opacity: 1;
    }

    .swal-photo-preview {
        max-height: 78vh;
        width: auto;
        object-fit: contain;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const allTruckApplianceIds = @json($truck->appliances->pluck('id')->values());

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

    function truckApplianceIds(onlySelected) {
        if (!onlySelected) {
            return allTruckApplianceIds;
        }

        const selector = onlySelected ? '[data-truck-appliance-checkbox]:checked' : '[data-truck-appliance-checkbox]';
        return $(selector).map(function () {
            return $(this).val();
        }).get();
    }

    function openPrintUrl(type, onlySelected) {
        const ids = truckApplianceIds(onlySelected);
        if (!ids.length) {
            alert('Please select at least one appliance.');
            return;
        }

        if (type === 'stickers') {
            const url = new URL('{{ route('admin.inventory.stickers') }}');
            url.searchParams.set('ids', ids.join(','));
            window.open(url.toString(), '_blank');
            return;
        }

        const url = new URL('{{ route('admin.inventory.index') }}');
        url.searchParams.set('print', '1');
        url.searchParams.set('ids', ids.join(','));
        window.open(url.toString(), '_blank');
    }

    $('[data-truck-select-all]').on('change', function () {
        $('[data-truck-appliance-checkbox]').prop('checked', $(this).is(':checked'));
    });

    $('[data-truck-select-all-button]').on('click', function () {
        $('[data-truck-appliance-checkbox]').prop('checked', true);
        $('[data-truck-select-all]').prop('checked', true);
    });

    $('[data-truck-reset-selection]').on('click', function () {
        $('[data-truck-appliance-checkbox], [data-truck-select-all]').prop('checked', false);
    });

    $('[data-truck-print-selected-sheets]').on('click', function () {
        openPrintUrl('sheets', true);
    });

    $('[data-truck-print-selected-stickers]').on('click', function () {
        openPrintUrl('stickers', true);
    });

    $('[data-truck-print-all-sheets]').on('click', function () {
        openPrintUrl('sheets', false);
    });

    $('[data-truck-print-all-stickers]').on('click', function () {
        openPrintUrl('stickers', false);
    });

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function buildPhotoGalleryHtml(payload) {
        const appliance = payload.appliance || {};
        const photos = payload.photos || [];
        const subtitle = [appliance.unit_label, appliance.serial_number].filter(Boolean).join(' | ');
        let body = '';

        if (!photos.length) {
            body = '<div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-gray-500"><i class="fas fa-images text-3xl text-gray-400"></i><p class="mt-3 text-sm font-semibold">No photos uploaded for this appliance.</p></div>';
        } else {
            body = '<div class="swal-photo-gallery">'
                + photos.map(function (photo) {
                    const photoUrl = escapeHtml(photo.url);

                    return '<button type="button" data-preview-photo="' + photoUrl + '">'
                        + '<img src="' + photoUrl + '" alt="Appliance photo">'
                        + '<span><i class="fas fa-search-plus mr-1"></i>View</span>'
                        + '</button>';
                }).join('')
                + '</div>';
        }

        return (subtitle ? '<p class="mb-4 text-sm text-gray-500">' + escapeHtml(subtitle) + '</p>' : '') + body;
    }

    function showPhotoGallery(payload) {
        Swal.fire({
            title: 'Appliance Photos',
            html: buildPhotoGalleryHtml(payload),
            showConfirmButton: false,
            showCloseButton: true,
            width: 'min(94vw, 980px)'
        });
    }

    $('[data-photo-modal-url]').on('click', function () {
        const url = $(this).data('photo-modal-url');

        Swal.fire({
            title: 'Appliance Photos',
            html: '<div class="py-8 text-center text-gray-600"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i><p class="mt-3 text-sm font-semibold">Loading photos...</p></div>',
            showConfirmButton: false,
            showCloseButton: true,
            allowOutsideClick: false,
            width: 'min(94vw, 980px)'
        });

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load photos.');
                }
                return response.json();
            })
            .then(function (payload) {
                window.currentPhotoGalleryPayload = payload;
                showPhotoGallery(payload);
            })
            .catch(function (error) {
                Swal.close();
                toastr.error(error.message || 'Unable to load photos.');
            });
    });

    $(document).on('click', '[data-preview-photo]', function () {
        Swal.fire({
            imageUrl: $(this).data('preview-photo'),
            imageAlt: 'Appliance photo',
            showConfirmButton: false,
            showCloseButton: true,
            width: 'min(94vw, 1080px)',
            padding: '1rem',
            background: '#fff',
            customClass: {
                image: 'swal-photo-preview'
            },
            didClose: function () {
                if (window.currentPhotoGalleryPayload) {
                    setTimeout(function () {
                        showPhotoGallery(window.currentPhotoGalleryPayload);
                    }, 80);
                }
            }
        });
    });
</script>
@endpush
