@extends('layouts.admin')

@section('title', 'Appliance Details')
@section('page-title', 'Appliance Details')

@section('content')
@php
    $status = $appliance->status ?: 'Triage';
    $baseCost = (float) $appliance->msrp;
    $partsCost = (float) $appliance->total_parts_cost;
    $finalCost = $baseCost + $partsCost;
    $soldPrice = $appliance->sold_price !== null ? (float) $appliance->sold_price : null;
    $profit = $soldPrice !== null ? $soldPrice - $finalCost : null;
    $modelNumber = $appliance->model?->model_number ?? ('#'.$appliance->id);
@endphp

<div class="inventory-detail-shell text-[11px] text-gray-900">
    <div class="flex items-center justify-between mb-3">
        <h1 class="text-xl font-semibold text-gray-900">Appliance Details: {{ $modelNumber }}</h1>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.inventory.index') }}" class="px-3 py-1 bg-gray-500 hover:bg-gray-600 text-white rounded text-xs">Back</a>
    </div>

    <section id="photos" class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Appliance Information</div>
        <div class="legacy-panel-body space-y-2">
            <p><strong>Unit Label:</strong> #{{ $appliance->id }}</p>
            <p><strong>Truck:</strong> @if($appliance->truck)<a href="{{ route('admin.trucks.show', $appliance->truck) }}" class="text-blue-700 underline">{{ $appliance->truck->name }}</a>@else - @endif</p>
            <p><strong>Model #:</strong> <span class="text-blue-700">{{ $modelNumber }}</span></p>
            <p><strong>Serial #:</strong> {{ $appliance->serial_number ?: '-' }}</p>
            <p><strong>Brand:</strong> {{ $appliance->brand ?: '-' }}</p>
            <p><strong>Category:</strong> {{ $appliance->category?->name ?? '-' }}</p>
            <p><strong>Current Status:</strong> {{ $status }}</p>
            <p><strong>Location:</strong> {{ $appliance->location ?: '-' }}</p>
            <p><strong>Total Cost:</strong> ${{ number_format($finalCost, 2) }}</p>
            <p><strong>Final Cost Valuation:</strong> ${{ number_format($finalCost, 2) }} <span class="text-gray-500">(Our Cost: ${{ number_format($baseCost, 2) }} + Parts Cost: ${{ number_format($partsCost, 2) }})</span></p>
            <p><strong>MSRP:</strong> ${{ number_format($appliance->msrp, 2) }}</p>
            @if($soldPrice !== null)
            <p><strong>Sold Price:</strong> ${{ number_format($appliance->sold_price, 2) }} <span class="text-gray-500">({{ $appliance->sold_by ?: 'Unknown' }}, {{ $appliance->sold_at?->format('Y-m-d H:i') }})</span></p>
            <p><strong>Profit:</strong> ${{ number_format($profit, 2) }} <span class="text-gray-500">(Sold Price - Final Cost Valuation)</span></p>
            @endif
        </div>
    </section>

    @canAccess('appliance.edit')
    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Update Location</div>
        <div class="legacy-panel-body">
            <form method="POST" action="{{ route('admin.inventory.location.update', $appliance) }}" class="space-y-2">
                @csrf
                @method('PATCH')
                <input type="text" name="location" value="{{ old('location', $appliance->location) }}" placeholder="Enter Location (e.g., Inventory Shelf 01)" class="legacy-input">
                <button type="submit" class="legacy-btn bg-blue-600">Update Location</button>
            </form>
        </div>
    </section>

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-yellow-400 text-black">Admin: Move Unit to Another Truck</div>
        <div class="legacy-panel-body">
            <form method="POST" action="{{ route('admin.inventory.move-truck.update', $appliance) }}" onsubmit="return confirm('Move this unit to the selected truck?');">
                @csrf
                @method('PATCH')
                <label class="block mb-1">Destination Truck</label>
                <select name="truck_id" class="legacy-input" required>
                    <option value="">Select a truck...</option>
                    @foreach($trucks as $truck)
                    <option value="{{ $truck->id }}" @selected((string) old('truck_id') === (string) $truck->id)>{{ $truck->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="legacy-btn bg-yellow-500 text-black mt-2">Move Unit</button>
            </form>
            <p class="mt-2 text-[10px] text-gray-500">This keeps parts, status history, and notes attached to the same unit record.</p>
        </div>
    </section>

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Actions</div>
        <div class="legacy-panel-body flex flex-wrap gap-1">
            @foreach(['Testing' => 'bg-blue-600', 'Cleaning' => 'bg-cyan-600', 'Ready' => 'bg-purple-600', 'Repair' => 'bg-yellow-500 text-black', 'Demanufacture' => 'bg-red-600', 'Show Room' => 'bg-green-600'] as $action => $class)
            <button type="button" class="legacy-btn {{ $class }}" data-status-shortcut="{{ $action }}">{{ $action }}</button>
            @endforeach
        </div>
    </section>

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Update Status</div>
        <div class="legacy-panel-body">
            <form method="POST" action="{{ route('admin.inventory.status.update', $appliance) }}" class="space-y-2" id="status-form">
                @csrf
                @method('PATCH')
                <select name="status" id="status-select" class="legacy-input max-w-xs">
                    <option value="">Select New Status</option>
                    @foreach($statuses as $option)
                    <option value="{{ $option }}" @selected(old('status') === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <textarea name="notes" placeholder="Notes" class="legacy-input min-h-12">{{ old('notes') }}</textarea>
                <div id="sold-price-row" class="hidden">
                    <label class="block mb-1">Sold Price <span class="text-gray-500">(optional)</span></label>
                    <input type="number" step="0.01" min="0" name="sold_price" value="{{ old('sold_price', $appliance->sold_price) }}" placeholder="Sold Price (excl. taxes)" class="legacy-input max-w-xs">
                </div>
                <label class="hidden items-center gap-2" id="parts-ordered-row">
                    <input type="checkbox" name="parts_ordered" value="1" @checked(old('parts_ordered'))>
                    <span>Parts Ordered</span>
                </label>
                <button type="submit" class="legacy-btn bg-blue-600">Update Status</button>
            </form>
        </div>
    </section>
    @endcanAccess

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Status History</div>
        <div class="legacy-panel-body">
            <div class="overflow-x-auto">
                <table class="legacy-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Timestamp</th>
                            <th>Notes</th>
                            <th>User</th>
                            <th>Parts Ordered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appliance->statusHistories->sortByDesc('created_at') as $history)
                        <tr>
                            <td>{{ $history->status }}</td>
                            <td>{{ $history->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $history->notes ?: 'N/A' }}</td>
                            <td>{{ $history->user?->name ?? '-' }}</td>
                            <td>{{ $history->parts_ordered ? 'Yes' : 'No' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td>{{ $status }}</td>
                            <td>{{ $appliance->updated_at?->format('Y-m-d H:i:s') }}</td>
                            <td>N/A</td>
                            <td>{{ $appliance->updater?->name ?? '-' }}</td>
                            <td>No</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Parts Used</div>
        <div class="legacy-panel-body">
            <div class="overflow-x-auto">
                <table class="legacy-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Cost</th>
                            <th>Source / Part #</th>
                            <th>Added</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($appliance->parts->sortByDesc('created_at') as $part)
                        <tr>
                            <td>{{ $part->description }}</td>
                            <td>${{ number_format($part->cost, 2) }}</td>
                            <td>{{ $part->part?->part_number ?: ($part->part_number ?: ($part->source ?: 'N/A')) }}</td>
                            <td>{{ $part->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @canAccess('appliance.edit')
                                <form method="POST" action="{{ route('admin.inventory.parts.destroy', [$appliance, $part]) }}" onsubmit="return confirm('Remove this part?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 underline">Delete</button>
                                </form>
                                @else
                                -
                                @endcanAccess
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No parts added yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @canAccess('appliance.edit')
    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-yellow-400 text-black">Add Part</div>
        <div class="legacy-panel-body">
            <form method="POST" action="{{ route('admin.inventory.parts.store', $appliance) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2" id="add-part-form">
                @csrf
                <input type="hidden" name="part_id" id="selected-part-id" value="{{ old('part_id') }}">
                <div>
                    <label>Part Description (Start typing to search inventory)</label>
                    <div class="relative">
                        <input name="description" id="part-description-input" value="{{ old('description') }}" placeholder="Search or enter manually..." class="legacy-input" autocomplete="off" required>
                        <div id="part-search-results" class="hidden absolute z-30 mt-1 w-full border border-gray-300 bg-white shadow max-h-56 overflow-y-auto"></div>
                    </div>
                </div>
                <div>
                    <label>Cost ($)</label>
                    <input type="number" step="0.01" min="0" name="cost" id="part-cost-input" value="{{ old('cost', 0) }}" class="legacy-input" required>
                </div>
                <div>
                    <label>Source / Part #</label>
                    <input id="part-number-preview" value="Auto-generated after save" class="legacy-input bg-gray-100 text-gray-600" readonly>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="legacy-btn bg-yellow-500 text-black">Add Part</button>
                </div>
            </form>
        </div>
    </section>
    @endcanAccess

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Photos</div>
        <div class="legacy-panel-body photo-panel-body">
            @canAccess('appliance.edit')
            <form method="POST" action="{{ route('admin.inventory.photos.store', $appliance) }}" enctype="multipart/form-data" class="photo-upload-form">
                @csrf
                <label class="photo-upload-dropzone">
                    <span class="photo-upload-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                    <span class="photo-upload-title">Choose appliance photos</span>
                    <span class="photo-upload-meta" id="photo-upload-meta">Up to 5 images at one time</span>
                    <input id="photo-upload-input" type="file" name="photos[]" accept="image/*" multiple required>
                </label>
                <button type="submit" class="legacy-btn bg-blue-600 photo-upload-button">Upload Photos</button>
            </form>
            @endcanAccess

            @if(count($appliance->photos ?? []))
            <div class="photo-grid">
                @foreach($appliance->photos as $photo)
                @php($photoUrl = route('admin.inventory.photos.show', ['appliance' => $appliance, 'photo' => $photo]))
                <div class="photo-card">
                    <button type="button" class="photo-thumb" data-photo-url="{{ $photoUrl }}" aria-label="View appliance photo">
                        <img src="{{ $photoUrl }}" alt="Appliance photo">
                        <span class="photo-view-chip"><i class="fas fa-search-plus"></i> View</span>
                    </button>
                    @canAccess('appliance.edit')
                    <form method="POST" action="{{ route('admin.inventory.photos.destroy', $appliance) }}" class="photo-delete-form" onsubmit="return confirm('Delete this image?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="photo" value="{{ $photo }}">
                        <button type="submit" class="photo-delete-button" title="Delete image"><i class="fas fa-trash"></i></button>
                    </form>
                    @endcanAccess
                </div>
                @endforeach
            </div>
            @else
            <div class="photo-empty-state">
                <i class="fas fa-images"></i>
                <span>No photos uploaded.</span>
            </div>
            @endif
        </div>
    </section>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('styles')
<style>
    .inventory-detail-shell {
        max-width: none;
    }

    .legacy-panel {
        border: 1px solid #c8c8c8;
        margin-bottom: 12px;
        background: #fff;
    }

    .legacy-panel-heading {
        color: #fff;
        font-weight: 700;
        padding: 7px 9px;
        line-height: 1;
    }

    .legacy-panel-body {
        padding: 9px;
    }

    .legacy-panel-body p {
        margin: 0 0 7px;
    }

    .legacy-input {
        width: 100%;
        border: 1px solid #bfc7d1;
        padding: 6px 8px;
        border-radius: 2px;
        background: #fff;
    }

    .legacy-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        border-radius: 3px;
        padding: 6px 10px;
        font-weight: 700;
        font-size: 11px;
    }

    .legacy-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    .legacy-table th {
        background: #2f98d4;
        color: #fff;
        text-align: left;
        padding: 7px;
        border: 1px solid #d8d8d8;
    }

    .legacy-table td {
        padding: 7px;
        border: 1px solid #d8d8d8;
        background: #f4f9fb;
    }

    .photo-panel-body {
        background: #f8fafc;
    }

    .photo-upload-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: stretch;
        margin-bottom: 14px;
    }

    .photo-upload-dropzone {
        position: relative;
        min-height: 70px;
        border: 1px dashed #8ab4f8;
        background: #fff;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
    }

    .photo-upload-dropzone:hover {
        border-color: #2563eb;
        background: #f5f9ff;
        box-shadow: 0 1px 4px rgba(37, 99, 235, 0.15);
    }

    .photo-upload-dropzone input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .photo-upload-icon {
        height: 38px;
        width: 38px;
        border-radius: 6px;
        background: #e8f0fe;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        flex: 0 0 auto;
    }

    .photo-upload-title {
        display: block;
        font-weight: 700;
        font-size: 13px;
        color: #1f2937;
    }

    .photo-upload-meta {
        display: block;
        color: #6b7280;
        margin-top: 3px;
    }

    .photo-upload-button {
        min-width: 128px;
        align-self: stretch;
    }

    .photo-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
    }

    .photo-card {
        position: relative;
        border: 1px solid #d7dee8;
        background: #fff;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }

    .photo-thumb {
        position: relative;
        display: block;
        width: 100%;
        aspect-ratio: 4 / 3;
        overflow: hidden;
        background: #e5e7eb;
    }

    .photo-thumb img {
        height: 100%;
        width: 100%;
        object-fit: cover;
        transition: transform 0.18s ease, filter 0.18s ease;
    }

    .photo-thumb:hover img {
        transform: scale(1.04);
        filter: brightness(0.82);
    }

    .photo-view-chip {
        position: absolute;
        left: 8px;
        bottom: 8px;
        opacity: 0;
        transform: translateY(4px);
        transition: opacity 0.18s ease, transform 0.18s ease;
        background: rgba(17, 24, 39, 0.82);
        color: #fff;
        border-radius: 999px;
        padding: 4px 8px;
        font-weight: 700;
        font-size: 10px;
    }

    .photo-thumb:hover .photo-view-chip {
        opacity: 1;
        transform: translateY(0);
    }

    .photo-delete-form {
        position: absolute;
        right: 7px;
        top: 7px;
    }

    .photo-delete-button {
        height: 28px;
        width: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: rgba(220, 38, 38, 0.92);
        color: #fff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    }

    .photo-delete-button:hover {
        background: #b91c1c;
    }

    .photo-empty-state {
        min-height: 92px;
        border: 1px dashed #cbd5e1;
        background: #fff;
        border-radius: 6px;
        color: #64748b;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 600;
    }

    .photo-empty-state i {
        font-size: 24px;
        color: #94a3b8;
    }

    .swal-appliance-photo {
        max-height: 78vh;
        width: auto;
        object-fit: contain;
        border-radius: 6px;
    }

    @media (max-width: 640px) {
        .photo-upload-form {
            grid-template-columns: 1fr;
        }

        .photo-upload-button {
            min-height: 42px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function syncStatusFields() {
        const status = $('#status-select').val();
        $('#sold-price-row').toggleClass('hidden', status !== 'Sold');
        $('#parts-ordered-row').toggleClass('hidden', status !== 'Holding for parts').toggleClass('flex', status === 'Holding for parts');
    }

    $('#status-select').on('change', syncStatusFields);
    syncStatusFields();

    $('[data-status-shortcut]').on('click', function () {
        $('#status-select').val($(this).data('status-shortcut')).trigger('change');
        document.getElementById('status-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    let partSearchTimer = null;
    const $partDescription = $('#part-description-input');
    const $partResults = $('#part-search-results');
    const $partId = $('#selected-part-id');
    const $partCost = $('#part-cost-input');
    const $partNumberPreview = $('#part-number-preview');

    function hidePartResults() {
        $partResults.addClass('hidden').empty();
    }

    function clearSelectedPart() {
        $partId.val('');
        $partNumberPreview.val('Auto-generated after save');
    }

    $partDescription.on('input', function () {
        const query = $(this).val().trim();
        clearSelectedPart();
        clearTimeout(partSearchTimer);

        if (query.length < 2) {
            hidePartResults();
            return;
        }

        partSearchTimer = setTimeout(function () {
            $.getJSON('{{ route('admin.inventory.parts.search') }}', { q: query })
                .done(function (parts) {
                    $partResults.empty();

                    if (!parts.length) {
                        // $partResults.append('<div class="px-3 py-2 text-gray-500">No matching parts found.</div>');
                    }

                    parts.forEach(function (part) {
                        const $row = $('<button type="button" class="block w-full px-3 py-2 text-left hover:bg-blue-50 border-b border-gray-100"></button>');
                        $row.append($('<div class="font-semibold text-gray-900"></div>').text(part.label));
                        $row.append($('<div class="text-gray-500"></div>').text('Cost: $' + Number(part.cost).toFixed(2) + ' | Stock: ' + part.stock));
                        $row.on('click', function () {
                            $partId.val(part.id);
                            $partDescription.val(part.description);
                            $partCost.val(Number(part.cost).toFixed(2));
                            $partNumberPreview.val(part.part_number);
                            hidePartResults();
                        });
                        $partResults.append($row);
                    });

                    $partResults.removeClass('hidden');
                });
        }, 250);
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('#add-part-form').length) {
            hidePartResults();
        }
    });

    $('[data-photo-url]').on('click', function () {
        Swal.fire({
            imageUrl: $(this).data('photo-url'),
            imageAlt: 'Appliance photo',
            showConfirmButton: false,
            showCloseButton: true,
            width: 'min(94vw, 1080px)',
            padding: '1rem',
            background: '#fff',
            customClass: {
                image: 'swal-appliance-photo'
            }
        });
    });

    $('#photo-upload-input').on('change', function () {
        const count = this.files.length;

        if (count > 5) {
            alert('You can upload a maximum of 5 images at one time.');
            this.value = '';
            $('#photo-upload-meta').text('Up to 5 images at one time');
            return;
        }

        $('#photo-upload-meta').text(count ? count + ' image' + (count === 1 ? '' : 's') + ' selected' : 'Up to 5 images at one time');
    });
</script>
@endpush
