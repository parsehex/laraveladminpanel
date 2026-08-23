@extends('layouts.admin')

@php
    $status = $appliance->status ?: 'Triage';
    $baseCost = (float) $appliance->msrp;
    $partsCost = $appliance->signedPartsCost();
    $finalCost = $baseCost + $partsCost;
    $soldPrice = $appliance->sold_price !== null ? (float) $appliance->sold_price : null;
    $cost = $appliance->salesCost(); $profit = (float) ($appliance->sold_price ?? 0) - $cost;
    $modelNumber = $appliance->model?->model_number ?? ('#'.$appliance->id);
    $heading = trim(implode(' ', array_filter([$appliance->brand, $modelNumber])));
    $unitLabel = $appliance->unit_label ?: null;
    $brandName = $appliance->brand ?: null;
    $serialNumber = $appliance->serial_number ?: null;
    $identityFields = [
        ['label' => 'Label', 'value' => $unitLabel, 'title' => 'Copy label'],
        ['label' => 'Brand', 'value' => $brandName, 'title' => 'Copy brand'],
        ['label' => 'Model #', 'value' => $modelNumber, 'title' => 'Copy model'],
        ['label' => 'Serial', 'value' => $serialNumber, 'title' => 'Copy serial'],
    ];
    $statusStyles = [
        'Triage' => ['label' => 'None / White', 'class' => 'status-white'],
        'Testing' => ['label' => 'Teal / Light Blue', 'class' => 'status-light-blue'],
        'Ready' => ['label' => 'Blue', 'class' => 'status-blue'],
        'Show Room' => ['label' => 'Purple', 'class' => 'status-purple'],
        'Holding' => ['label' => 'Pink', 'class' => 'status-pink'],
        'Repair' => ['label' => 'Orange', 'class' => 'status-orange'],
        'Holding for parts' => ['label' => 'Yellow', 'class' => 'status-yellow'],
        'Cleaning' => ['label' => 'Brown', 'class' => 'status-brown'],
        'Demanufacture' => ['label' => 'Red', 'class' => 'status-red'],
        'Scrap' => ['label' => 'Black', 'class' => 'status-black'],
        'Quality Control QC' => ['label' => 'Green', 'class' => 'status-green'],
        'Sold' => ['label' => 'Sold', 'class' => 'status-sold'],
    ];
    $statusClass = $statusStyles[$status]['class'] ?? 'status-white';
@endphp

@section('title', $heading)
@section('page-title', $heading)

@section('page-actions')
    <div class="relative" x-data="{ open: false }">
        <button type="button"
                @click="open = !open"
                class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            <i class="fas fa-print mr-2"></i>Print
            <i class="fas fa-chevron-down ml-2 text-xs opacity-80"></i>
        </button>
        <div x-cloak
             x-show="open"
             @click.outside="open = false"
             class="absolute left-0 z-[1001] mt-2 w-44 rounded-md border border-slate-200 bg-white py-1 shadow-lg">
            <a href="{{ route('admin.inventory.stickers', ['ids' => $appliance->id]) }}"
               target="_blank"
               class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                <i class="fas fa-qrcode w-4 mr-2 text-emerald-600"></i>Sticker
            </a>
            <a href="{{ route('admin.inventory.index', ['print' => 1, 'ids' => $appliance->id]) }}"
               target="_blank"
               class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                <i class="fas fa-file-alt w-4 mr-2 text-blue-600"></i>Sheet
            </a>
        </div>
    </div>
    <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
        Back to inventory
    </a>
@endsection

@section('content')
<div class="inventory-detail-shell text-[13px] text-gray-900">
    <div class="mb-4 flex flex-wrap items-center gap-x-2 gap-y-1">
        <span class="status-chip {{ $statusClass }}">{{ $status }}</span>
        @foreach($identityFields as $index => $field)
            @if($index > 0)
            <span class="text-gray-300">·</span>
            @endif
            <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                <span>{{ $field['label'] }}: {{ $field['value'] ?: '—' }}</span>
                @if($field['value'])
                <button type="button" data-copy-text="{{ $field['value'] }}" class="identity-copy-btn" title="{{ $field['title'] }}">
                    <i class="fas fa-copy"></i>
                </button>
                @endif
            </span>
        @endforeach
    </div>

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Appliance Information</div>
        <div class="legacy-panel-body">
            <dl class="appliance-facts">
                <div>
                    <dt>Truck</dt>
                    <dd>
                        @if($appliance->truck)
                        <a href="{{ route('admin.trucks.show', $appliance->truck) }}" class="text-blue-700 underline">{{ $appliance->truck->name }}</a>
                        @else
                        —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Location</dt>
                    <dd>{{ $appliance->location ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Category</dt>
                    <dd>{{ $appliance->category?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Subcategory</dt>
                    <dd>{{ $appliance->subcategory ?: '—' }}</dd>
                </div>
            </dl>
            <dl class="appliance-costs">
                <div>
                    <dt>Total Cost</dt>
                    <dd>${{ number_format(0, 2) }}</dd>
                </div>
                <div>
                    <dt>Final Cost Valuation</dt>
                    <dd>${{ number_format($appliance->price, 2) }}</dd>
                    <p>Our Cost: ${{ number_format($appliance->price, 2) }} {{ $partsCost < 0 ? '-' : '+' }} Parts Cost: ${{ number_format(abs($partsCost), 2) }}</p>
                </div>
                <div>
                    <dt>MSRP</dt>
                    <dd>${{ number_format($appliance->msrp, 2) }}</dd>
                </div>
                @if($soldPrice !== null && $appliance->sold_by !== null)
                <div>
                    <dt>Sold Price</dt>
                    <dd>${{ number_format($appliance->sold_price, 2) }}</dd>
                    <p>{{ $appliance->sold_by ?: 'Unknown' }}, {{ $appliance->sold_at?->format('Y-m-d H:i') }}</p>
                </div>
                <div>
                    <dt>Profit</dt>
                    <dd>${{ number_format($profit, 2) }}</dd>
                    <p>Sold Price - Final Cost Valuation</p>
                </div>
                @endif
            </dl>
        </div>
    </section>

    @canAccess('appliance.edit')
    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Update Location</div>
        <div class="legacy-panel-body">
            <form method="POST" action="{{ route('admin.inventory.location.update', $appliance) }}" class="flex flex-col gap-2 sm:flex-row sm:items-start">
                @csrf
                @method('PATCH')
                <div class="relative min-w-0 flex-1" data-location-picker>
                    <input type="text" name="location" id="location-input" value="{{ old('location', $appliance->location) }}" placeholder="Search or enter a location..." class="legacy-input location-picker-input" autocomplete="off">
                    <button type="button" class="location-picker-toggle" data-location-toggle aria-label="Show used locations">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="location-results" class="location-suggestion-menu hidden" role="listbox"></div>
                </div>
                <button type="submit" class="legacy-btn bg-blue-600 location-picker-submit">Update Location</button>
            </form>
        </div>
    </section>

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-yellow-400 text-black">Admin: Move Unit to Another Truck</div>
        <div class="legacy-panel-body">
            <form method="POST" action="{{ route('admin.inventory.move-truck.update', $appliance) }}" class="flex flex-col gap-2 sm:flex-row sm:items-start" onsubmit="return confirm('Move this unit to the selected truck?');">
                @csrf
                @method('PATCH')
                <select name="truck_id" class="legacy-input min-w-0 flex-1" required aria-label="Destination Truck">
                    <option value="">Destination Truck...</option>
                    @foreach($trucks as $truck)
                    <option value="{{ $truck->id }}" @selected((string) old('truck_id') === (string) $truck->id)>{{ $truck->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="legacy-btn bg-yellow-500 text-black location-picker-submit">Move Unit</button>
            </form>
            <p class="mt-2 text-[10px] text-gray-500">This keeps parts, status history, and notes attached to the same unit record.</p>
        </div>
    </section>

    @if($status === 'Testing')
    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-sky-600">Testing checklist</div>
        <div class="legacy-panel-body">
            <a href="{{ route('admin.inventory.testing.show', $appliance) }}" class="legacy-btn bg-sky-600 inline-flex items-center">
                <i class="fas fa-clipboard-check mr-2"></i>Start Testing
            </a>
            <p class="mt-2 text-[10px] text-gray-500">Walk the category checklist, then the unit status updates from the result.</p>
        </div>
    </section>
    @endif

    @if($status === 'Repair')
    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-orange-600">Repair / triage</div>
        <div class="legacy-panel-body">
            <a href="{{ route('admin.inventory.repair.show', $appliance) }}" class="legacy-btn bg-orange-600 inline-flex items-center">
                <i class="fas fa-wrench mr-2"></i>Open Repair
            </a>
            <p class="mt-2 text-[10px] text-gray-500">Log diagnosis notes and re-test failed checklist steps.</p>
        </div>
    </section>
    @endif

    <section class="legacy-panel">
        <div class="legacy-panel-heading bg-blue-600">Actions</div>
        <div class="legacy-panel-body flex flex-wrap gap-1">
            @foreach(['Testing', 'Cleaning', 'Ready', 'Repair', 'Holding', 'Holding for parts', 'Demanufacture', 'Show Room', 'Quality Control QC'] as $action)
            <button type="button" class="legacy-btn status-action {{ $statusStyles[$action]['class'] ?? 'status-white' }}" data-status-shortcut="{{ $action }}">{{ $action }}</button>
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
                    <label class="block mb-1">Sold Price <span class="text-gray-500"></span></label>
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
        <div class="legacy-panel-heading bg-blue-600 flex items-center justify-between gap-2">
            <span>Status History</span>
            <div class="flex items-center gap-3 text-xs font-semibold text-white/90">
                @if(($testingResultCount ?? 0) > 0)
                <a href="{{ route('admin.inventory.testing-results.index', $appliance) }}" class="underline hover:text-white">
                    Testing results ({{ $testingResultCount }})
                </a>
                @endif
                @if(($repairResultCount ?? 0) > 0)
                <a href="{{ route('admin.inventory.repair-results.index', $appliance) }}" class="underline hover:text-white">
                    Repair results ({{ $repairResultCount }})
                </a>
                @endif
            </div>
        </div>
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
                        @php($rowClass = $statusStyles[$history->status]['class'] ?? 'status-white')
                        @php($resultId = $testingResultLinks[$history->id] ?? null)
                        @php($repairResultId = $repairResultLinks[$history->id] ?? null)
                        <tr class="status-row {{ $rowClass }}">
                            <td><span class="status-chip {{ $rowClass }}">{{ $history->status }}</span></td>
                            <td>
                                @if($resultId)
                                <a href="{{ route('admin.inventory.testing-results.show', [$appliance, $resultId]) }}" class="text-blue-700 underline font-medium" title="View testing result">{{ $history->created_at?->format('Y-m-d H:i:s') }}</a>
                                @elseif($repairResultId)
                                <a href="{{ route('admin.inventory.repair-results.show', [$appliance, $repairResultId]) }}" class="text-blue-700 underline font-medium" title="View repair re-evaluation">{{ $history->created_at?->format('Y-m-d H:i:s') }}</a>
                                @else
                                {{ $history->created_at?->format('Y-m-d H:i:s') }}
                                @endif
                            </td>
                            <td>{{ $history->displayNotes() }}</td>
                            <td>{{ $history->user?->name ?? '-' }}</td>
                            <td>{{ $history->parts_ordered ? 'Yes' : 'No' }}</td>
                        </tr>
                        @empty
                        <tr class="status-row {{ $statusClass }}">
                            <td><span class="status-chip {{ $statusClass }}">{{ $status }}</span></td>
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

    @include('admin.inventory.partials.parts-panel')

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

    .identity-copy-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 1.5rem;
        width: 1.5rem;
        min-height: 0;
        border-radius: 0.25rem;
        color: #6b7280;
        font-size: 11px;
    }

    .identity-copy-btn:hover {
        background: #f3f4f6;
        color: #1f2937;
    }

    .appliance-facts,
    .appliance-costs {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px 16px;
        margin: 0;
    }

    .appliance-costs {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }

    .appliance-facts dt,
    .appliance-costs dt {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        margin: 0 0 3px;
    }

    .appliance-facts dd,
    .appliance-costs dd {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: #111827;
    }

    .appliance-costs p {
        margin: 3px 0 0;
        color: #6b7280;
        font-size: 11px;
        font-weight: 500;
    }

    @media (max-width: 900px) {
        .appliance-facts,
        .appliance-costs {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 480px) {
        .appliance-facts,
        .appliance-costs {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .location-picker-input {
        padding-right: 2.5rem;
    }

    .location-picker-toggle {
        position: absolute;
        right: 0.35rem;
        top: 50%;
        transform: translateY(-50%);
        height: 2rem;
        width: 2rem;
        min-height: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        border-radius: 0.35rem;
    }

    .location-picker-toggle:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .location-picker-submit {
        min-width: 9.5rem;
        min-height: 2.75rem;
    }

    .location-suggestion-menu {
        position: absolute;
        top: calc(100% + 0.35rem);
        left: 0;
        right: 0;
        z-index: 40;
        max-height: 16rem;
        overflow-y: auto;
        padding: 0.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: #fff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.14);
    }

    .location-suggestion {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        min-height: 0;
        padding: 0.55rem 0.7rem;
        border-radius: 0.375rem;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    .location-suggestion:hover,
    .location-suggestion.is-active {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .location-suggestion-count {
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .location-suggestion-empty {
        padding: 0.7rem 0.75rem;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
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

    .status-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid rgba(15, 23, 42, 0.18);
        border-radius: 999px;
        padding: 3px 8px;
        font-weight: 800;
        line-height: 1.1;
        min-height: 20px;
    }

    .status-action {
        border: 1px solid rgba(15, 23, 42, 0.18);
        box-shadow: none;
    }

    .legacy-table tr.status-row td {
        font-weight: 600;
    }

    .legacy-table tr.status-row.status-white td { background: #ffffff; color: #111827; }
    .legacy-table tr.status-row.status-light-blue td { background: #d8f3ff; color: #075985; }
    .legacy-table tr.status-row.status-blue td { background: #bfdbfe; color: #1e3a8a; }
    .legacy-table tr.status-row.status-purple td { background: #e9d5ff; color: #581c87; }
    .legacy-table tr.status-row.status-pink td { background: #fbcfe8; color: #831843; }
    .legacy-table tr.status-row.status-orange td { background: #fed7aa; color: #7c2d12; }
    .legacy-table tr.status-row.status-yellow td { background: #fef08a; color: #713f12; }
    .legacy-table tr.status-row.status-brown td { background: #d7b899; color: #422006; }
    .legacy-table tr.status-row.status-red td { background: #fecaca; color: #7f1d1d; }
    .legacy-table tr.status-row.status-black td { background: #111827; color: #ffffff; }
    .legacy-table tr.status-row.status-green td { background: #bbf7d0; color: #14532d; }
    .legacy-table tr.status-row.status-sold td { background: #dcfce7; color: #166534; }

    .status-white { background: #ffffff !important; color: #111827 !important; }
    .status-light-blue { background: #d8f3ff !important; color: #075985 !important; }
    .status-blue { background: #60a5fa !important; color: #ffffff !important; }
    .status-purple { background: #a855f7 !important; color: #ffffff !important; }
    .status-pink { background: #f472b6 !important; color: #ffffff !important; }
    .status-orange { background: #fb923c !important; color: #111827 !important; }
    .status-yellow { background: #fde047 !important; color: #111827 !important; }
    .status-brown { background: #92400e !important; color: #ffffff !important; }
    .status-red { background: #ef4444 !important; color: #ffffff !important; }
    .status-black { background: #111827 !important; color: #ffffff !important; }
    .status-green { background: #22c55e !important; color: #052e16 !important; }
    .status-sold { background: #16a34a !important; color: #ffffff !important; }

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
    $('[data-copy-text]').on('click', function () {
        const $button = $(this);
        const value = String($button.attr('data-copy-text') || '');
        const $icon = $button.find('i');

        if (!value || !navigator.clipboard) {
            return;
        }

        navigator.clipboard.writeText(value).then(function () {
            $icon.removeClass('fa-copy').addClass('fa-check');
            setTimeout(function () {
                $icon.removeClass('fa-check').addClass('fa-copy');
            }, 1200);
        });
    });

    function syncStatusFields() {
        const status = $('#status-select').val();
        const isSold = status === 'Sold';
        $('#sold-price-row').toggleClass('hidden', status !== 'Sold');
        $('#sold-price-row input[name="sold_price"]').prop('required', isSold);

        $('#parts-ordered-row').toggleClass('hidden', status !== 'Holding for parts').toggleClass('flex', status === 'Holding for parts');
    }

    $('#status-select').on('change', syncStatusFields);
    syncStatusFields();

    $('[data-status-shortcut]').on('click', function () {
        $('#status-select').val($(this).data('status-shortcut')).trigger('change');
        document.getElementById('status-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    const locationOptions = @json($locations);
    const $locationPicker = $('[data-location-picker]');
    const $locationInput = $('#location-input');
    const $locationResults = $('#location-results');
    let locationActiveIndex = -1;

    function filteredLocations(query) {
        const normalized = query.trim().toLowerCase();

        if (!normalized) {
            return locationOptions.slice(0, 12);
        }

        return locationOptions
            .filter(function (option) {
                return option.label.toLowerCase().includes(normalized);
            })
            .slice(0, 12);
    }

    function hideLocationResults() {
        $locationResults.addClass('hidden').empty();
        locationActiveIndex = -1;
    }

    function renderLocationResults(query) {
        const matches = filteredLocations(query);
        $locationResults.empty();
        locationActiveIndex = -1;

        if (!matches.length) {
            $locationResults.append(
                $('<div class="location-suggestion-empty"></div>').text(
                    query.trim() ? 'No matching locations. Press Update to save a new one.' : 'No saved locations yet.'
                )
            );
            $locationResults.removeClass('hidden');
            return;
        }

        matches.forEach(function (option, index) {
            const unitLabel = option.count === 1 ? 'unit' : 'units';
            const $row = $('<button type="button" class="location-suggestion" role="option"></button>');
            $row.append($('<span></span>').text(option.label));
            $row.append($('<span class="location-suggestion-count"></span>').text(option.count + ' ' + unitLabel));
            $row.on('mouseenter', function () {
                locationActiveIndex = index;
                $locationResults.find('.location-suggestion').removeClass('is-active');
                $row.addClass('is-active');
            });
            $row.on('click', function () {
                $locationInput.val(option.label);
                hideLocationResults();
                $locationInput.trigger('focus');
            });
            $locationResults.append($row);
        });

        $locationResults.removeClass('hidden');
    }

    function moveLocationSelection(direction) {
        const $items = $locationResults.find('.location-suggestion');
        if (!$items.length || $locationResults.hasClass('hidden')) {
            return;
        }

        locationActiveIndex = Math.max(0, Math.min($items.length - 1, locationActiveIndex + direction));
        $items.removeClass('is-active').eq(locationActiveIndex).addClass('is-active');
    }

    $locationInput.on('focus input', function () {
        renderLocationResults($(this).val());
    });

    $('[data-location-toggle]').on('click', function () {
        if ($locationResults.hasClass('hidden')) {
            renderLocationResults($locationInput.val());
            $locationInput.trigger('focus');
            return;
        }

        hideLocationResults();
    });

    $locationInput.on('keydown', function (event) {
        if ($locationResults.hasClass('hidden')) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            moveLocationSelection(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            moveLocationSelection(-1);
        } else if (event.key === 'Enter' && locationActiveIndex >= 0) {
            event.preventDefault();
            $locationResults.find('.location-suggestion').eq(locationActiveIndex).trigger('click');
        } else if (event.key === 'Escape') {
            hideLocationResults();
        }
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('[data-location-picker]').length) {
            hideLocationResults();
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
@include('admin.inventory.partials.parts-search-script')
@endpush
