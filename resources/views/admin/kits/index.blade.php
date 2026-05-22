@extends('layouts.admin')

@section('title', 'Kits Management')
@section('page-title', 'Kits Management')

@php
    $statusClasses = [
        'pending' => 'bg-amber-100 text-amber-800',
        'in_progress' => 'bg-cyan-100 text-cyan-800',
        'built' => 'bg-orange-100 text-orange-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
    ];
@endphp

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Kits Management</h1>
        <p class="mt-1 text-sm text-gray-500">Build kit definitions, assign batches, and track raw and finished stock.</p>
    </div>

    @if($canManage)
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h2 class="text-xl font-semibold text-white">Kit Management</h2>
            </div>
            <form method="POST" action="{{ route('admin.kits.store') }}" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input name="code" value="{{ old('code') }}" placeholder="Kit Code" class="px-3 py-2 border border-gray-300 rounded-md" required>
                    <input name="name" value="{{ old('name') }}" placeholder="Name" class="px-3 py-2 border border-gray-300 rounded-md" required>
                    <textarea name="sop" placeholder="SOP (optional)" class="px-3 py-2 border border-gray-300 rounded-md md:col-span-2">{{ old('sop') }}</textarea>
                    <input type="number" min="0" name="amazon_min_level" value="{{ old('amazon_min_level') }}" placeholder="Amazon Min Lvl (0)" class="px-3 py-2 border border-gray-300 rounded-md">
                    <input type="number" min="0" name="shopify_min_level" value="{{ old('shopify_min_level') }}" placeholder="Shopify Min Lvl (0)" class="px-3 py-2 border border-gray-300 rounded-md">
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-base font-semibold text-gray-900">Parts for Kit</h3>
                    <div id="parts-fields" class="mt-3 space-y-2">
                        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_160px] gap-2" data-kit-part-row>
                            <div class="flex gap-2" data-quick-create-wrapper>
                                <select name="part_name[]" data-ajax-dropdown="kit_part" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Search part...</option>
                                </select>
                                <button type="button" class="mt-1 h-8 w-8 flex-shrink-0 rounded-md bg-blue-600 text-xs text-white hover:bg-blue-700" title="Add part" data-open-quick-create="kit_part">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <input name="quantity_per_kit[]" type="number" min="1" placeholder="Qty per Kit" class="px-3 py-2 border border-gray-300 rounded-md" data-kit-part-qty>
                        </div>
                    </div>
                    <button type="button" data-add-part="#parts-fields" class="mt-3 px-4 py-2 rounded-md bg-gray-500 text-white font-semibold">Add Another Part</button>
                </div>

                <div class="flex justify-end">
                    <button class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold">Add Kit</button>
                </div>
            </form>

            <form method="POST" action="" class="border-t border-gray-200 p-6" data-delete-kit-form>
                @csrf
                @method('DELETE')
                <h3 class="text-base font-semibold text-gray-900">Delete Kit</h3>
                <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <select class="px-3 py-2 border border-gray-300 rounded-md md:col-span-3" data-delete-kit-select required>
                        <option value="">Select Kit</option>
                        @foreach($kits as $kit)
                            <option value="{{ route('admin.kits.destroy', $kit) }}">{{ $kit->code }} - {{ $kit->name }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 rounded-md bg-red-600 text-white font-semibold" onclick="return confirm('Delete this kit?')">Delete Kit</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <h2 class="text-xl font-semibold text-white">Edit Kit Parts</h2>
            </div>
            <div class="p-6">
                <form method="GET" action="{{ route('admin.kits.index') }}">
                    <select name="edit_kit" class="w-full px-3 py-2 border border-gray-300 rounded-md" onchange="this.form.submit()">
                        <option value="">Select Kit to Edit</option>
                        @foreach($kits as $kit)
                            <option value="{{ $kit->id }}" @selected($editKit?->id === $kit->id)>{{ $kit->code }} - {{ $kit->name }}</option>
                        @endforeach
                    </select>
                </form>

                @if($editKit)
                <div class="mt-5">
                    <h3 class="text-base font-semibold text-gray-900">Current Parts</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Part Name</th><th class="px-4 py-3 text-left">Quantity per Kit</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($editKit->parts as $part)
                                <tr>
                                    <td class="px-4 py-3">{{ $part->part_name }}</td>
                                    <td class="px-4 py-3">{{ $part->quantity_per_kit }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.kits.parts.destroy', [$editKit, $part]) }}" onsubmit="return confirm('Delete this part?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 font-semibold">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No parts.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.kits.parts.store', $editKit) }}" class="mt-5 border-t border-gray-200 pt-4">
                    @csrf
                    <h3 class="text-base font-semibold text-gray-900">Add New Parts</h3>
                    <div id="edit-parts-fields" class="mt-3 space-y-2">
                        <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_160px] gap-2" data-kit-part-row>
                            <div class="flex gap-2" data-quick-create-wrapper>
                                <select name="part_name[]" data-ajax-dropdown="kit_part" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Search part...</option>
                                </select>
                                <button type="button" class="mt-1 h-8 w-8 flex-shrink-0 rounded-md bg-blue-600 text-xs text-white hover:bg-blue-700" title="Add part" data-open-quick-create="kit_part">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <input name="quantity_per_kit[]" type="number" min="1" placeholder="Qty per Kit" class="px-3 py-2 border border-gray-300 rounded-md" data-kit-part-qty>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap justify-end gap-2">
                        <button type="button" data-add-part="#edit-parts-fields" class="px-4 py-2 rounded-md bg-gray-500 text-white font-semibold">Add Another Part</button>
                        <button class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold">Save New Parts</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Assign New Kit Batch</h2>
        </div>
        <form method="POST" action="{{ route('admin.kits.assignments.store') }}" class="p-6 grid grid-cols-1 md:grid-cols-6 gap-3">
            @csrf
            <select name="kit_id" class="px-3 py-2 border border-gray-300 rounded-md" required>
                <option value="">Select Kit</option>
                @foreach($kits as $kit)
                    <option value="{{ $kit->id }}">{{ $kit->code }} - {{ $kit->name }}</option>
                @endforeach
            </select>
            <input type="number" min="1" name="quantity" placeholder="Qty" class="px-3 py-2 border border-gray-300 rounded-md" required>
            <select name="platform" class="px-3 py-2 border border-gray-300 rounded-md" required>
                <option value="">Platform</option>
                <option value="amazon">Amazon</option>
                <option value="shopify">Shopify</option>
            </select>
            <select name="assigned_to" class="px-3 py-2 border border-gray-300 rounded-md" required>
                <option value="">Select Maker</option>
                @foreach($makers as $maker)
                    <option value="{{ $maker->id }}">{{ $maker->name }} ({{ $maker->platform ?: 'All' }})</option>
                @endforeach
            </select>
            <input type="date" name="due_date" class="px-3 py-2 border border-gray-300 rounded-md" required>
            <button class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold">Assign</button>
        </form>
    </div>
    @endif

    @include('admin.kits.partials.assignments-table', ['title' => 'Assignments'.($canManage ? '' : ' (My Queue)'), 'headerClass' => 'bg-cyan-600', 'rows' => $assignments, 'showConfirm' => false, 'canManage' => $canManage])

    @if($canManage && $builtAssignments->isNotEmpty())
        @include('admin.kits.partials.assignments-table', ['title' => 'Waiting for Confirmation', 'headerClass' => 'bg-amber-500', 'rows' => $builtAssignments, 'showConfirm' => true, 'canManage' => $canManage])
    @endif

    @if($canManage)
        @include('admin.kits.partials.assignments-table', ['title' => 'Completed Assignments', 'headerClass' => 'bg-emerald-600', 'rows' => $completedAssignments, 'showConfirm' => false, 'canManage' => $canManage, 'completed' => true])

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gray-600 px-6 py-4"><h2 class="text-xl font-semibold text-white">Completed Kits</h2></div>
                <div class="p-6 space-y-5">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Kit</th><th class="px-4 py-3 text-right">Amazon Stock</th><th class="px-4 py-3 text-right">Amazon Min</th><th class="px-4 py-3 text-right">Shopify Stock</th><th class="px-4 py-3 text-right">Shopify Min</th><th class="px-4 py-3 text-left">Status</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($kits as $kit)
                                    @php($item = $finishedKits->get($kit->code))
                                    @php($amazonLow = $item && $item->amazon_stock < $item->amazon_min_level)
                                    @php($shopifyLow = $item && $item->shopify_stock < $item->shopify_min_level)
                                    <tr class="{{ $amazonLow || $shopifyLow ? 'bg-red-50' : '' }}">
                                        <td class="px-4 py-3 font-semibold">{{ $kit->code }} - {{ $kit->name }}</td>
                                        <td class="px-4 py-3 text-right">{{ $item?->amazon_stock ?? 0 }}</td>
                                        <td class="px-4 py-3 text-right">{{ $item?->amazon_min_level ?? 0 }}</td>
                                        <td class="px-4 py-3 text-right">{{ $item?->shopify_stock ?? 0 }}</td>
                                        <td class="px-4 py-3 text-right">{{ $item?->shopify_min_level ?? 0 }}</td>
                                        <td class="px-4 py-3">{{ $amazonLow ? 'Amazon Low ' : '' }}{{ $shopifyLow ? 'Shopify Low' : '' }}{{ ! $amazonLow && ! $shopifyLow ? 'OK' : '' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No completed kits.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.kits.partials.stock-forms', ['items' => $kits->map(fn($kit) => (object) ['part_name' => $kit->code, 'label' => $kit->code.' - '.$kit->name]), 'platformRequired' => true])
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-gray-600 px-6 py-4"><h2 class="text-xl font-semibold text-white">Raw Inventory (Shared)</h2></div>
                <div class="p-6 space-y-5">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left">Part Name</th><th class="px-4 py-3 text-right">Current Stock</th><th class="px-4 py-3 text-right">Min Level</th><th class="px-4 py-3 text-left">Status</th></tr></thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($rawResources as $item)
                                    <tr class="{{ $item->current_stock < $item->min_level ? 'bg-red-50' : '' }}">
                                        <td class="px-4 py-3 font-semibold">{{ $item->part_name }}</td>
                                        <td class="px-4 py-3 text-right">{{ $item->current_stock }}</td>
                                        <td class="px-4 py-3 text-right">{{ $item->min_level }}</td>
                                        <td class="px-4 py-3">{{ $item->current_stock < $item->min_level ? 'Low Stock' : 'OK' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No raw resources.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.kits.partials.stock-forms', ['items' => $rawResources->map(fn($item) => (object) ['part_name' => $item->part_name, 'label' => $item->part_name]), 'platformRequired' => false])

                    <form method="POST" action="{{ route('admin.kits.resources.store') }}" class="border-t border-gray-200 pt-5">
                        @csrf
                        <h3 class="text-base font-semibold text-gray-900">Add New Raw Resource</h3>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <input name="part_name" placeholder="Part Name" class="px-3 py-2 border border-gray-300 rounded-md" required>
                            <input type="number" name="initial_stock" placeholder="Initial Stock" class="px-3 py-2 border border-gray-300 rounded-md">
                            <input type="number" min="0" name="min_level" placeholder="Min Level" class="px-3 py-2 border border-gray-300 rounded-md">
                            <button class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold">Add Resource</button>
                        </div>
                    </form>

                    <form method="POST" action="" class="border-t border-gray-200 pt-5" data-delete-resource-form>
                        @csrf
                        @method('DELETE')
                        <h3 class="text-base font-semibold text-gray-900">Delete Raw Resource</h3>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <select class="px-3 py-2 border border-gray-300 rounded-md md:col-span-3" data-delete-resource-select required>
                                <option value="">Select Part</option>
                                @foreach($rawResources as $item)
                                    <option value="{{ route('admin.kits.resources.destroy', $item) }}">{{ $item->part_name }}</option>
                                @endforeach
                            </select>
                            <button class="px-4 py-2 rounded-md bg-red-600 text-white font-semibold" onclick="return confirm('Delete this resource?')">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($selectedAssignment)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-emerald-600 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-white">Messages for Assignment #{{ $selectedAssignment->id }} ({{ $selectedAssignment->kit?->code }})</h2>
            <a href="{{ route('admin.kits.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-800">Back</a>
        </div>
        <div class="p-6">
            <div class="max-h-80 overflow-y-auto rounded-md border border-gray-200 p-4 space-y-3">
                @forelse($selectedAssignment->messages as $message)
                    <div class="{{ $message->sender_id === auth()->id() ? 'text-right' : '' }}">
                        <p class="font-semibold text-gray-900">{{ $message->sender?->name ?? 'Staff' }}</p>
                        <p class="text-sm text-gray-700">{{ $message->message }}</p>
                        <p class="text-xs text-gray-500">{{ $message->created_at?->format('M j, Y g:i A') }}</p>
                    </div>
                @empty
                    <p class="text-center text-gray-500">No messages yet.</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('admin.kits.messages.store', $selectedAssignment) }}" class="mt-3 flex gap-2">
                @csrf
                <input name="message" class="flex-1 px-3 py-2 border border-gray-300 rounded-md" placeholder="Type message..." required>
                <button class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold">Send</button>
            </form>
        </div>
    </div>
    @endif
</div>

<div id="sop-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold text-gray-900">SOP & Parts List</h3>
            <button type="button" id="close-sop-modal" class="h-10 w-10 rounded-md border border-gray-200 text-gray-700">X</button>
        </div>
        <div id="sop-content" class="mt-4 text-sm text-gray-700">Loading...</div>
    </div>
</div>

@include('admin.shared.ajax-dropdowns')
@endsection

@push('scripts')
<script>
    function kitPartRow() {
        return `
            <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_160px] gap-2" data-kit-part-row>
                <div class="flex gap-2" data-quick-create-wrapper>
                    <select name="part_name[]" data-ajax-dropdown="kit_part" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Search part...</option>
                    </select>
                    <button type="button" class="mt-1 h-8 w-8 flex-shrink-0 rounded-md bg-blue-600 text-xs text-white hover:bg-blue-700" title="Add part" data-open-quick-create="kit_part">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <input name="quantity_per_kit[]" type="number" min="1" placeholder="Qty per Kit" class="px-3 py-2 border border-gray-300 rounded-md" data-kit-part-qty>
            </div>
        `;
    }

    $('[data-add-part]').on('click', function () {
        const target = $($(this).data('add-part'));
        target.append(kitPartRow());
        window.initializeAjaxDropdowns && window.initializeAjaxDropdowns();
    });

    $(document).on('select2:select change', '[data-ajax-dropdown="kit_part"]', function (event) {
        const $select = $(this);
        const selected = event.params && event.params.data ? event.params.data : {};
        const optionStock = $select.find(':selected').data('stock');
        const stock = selected.stock !== undefined ? selected.stock : optionStock;
        const $qty = $select.closest('[data-kit-part-row]').find('[data-kit-part-qty]');

        if (stock !== undefined && stock !== '') {
            $qty.attr('max', stock);
            $qty.attr('title', 'Available stock: ' + stock);
        } else {
            $qty.removeAttr('max').removeAttr('title');
        }
    });

    $('[data-delete-kit-select]').on('change', function () {
        $('[data-delete-kit-form]').attr('action', this.value);
    });

    $('[data-delete-resource-select]').on('change', function () {
        $('[data-delete-resource-form]').attr('action', this.value);
    });

    $('[data-sop-url]').on('click', function () {
        $('#sop-modal').removeClass('hidden').addClass('flex');
        $('#sop-content').text('Loading...');
        $.get($(this).data('sop-url'), function (data) {
            const parts = (data.parts || []).map(part => `<li>${part.part_name} x ${part.quantity_per_kit}</li>`).join('');
            $('#sop-content').html(`<h4 class="font-semibold text-gray-900">${data.name || ''}</h4><p class="mt-2 whitespace-pre-line"><strong>Instructions:</strong> ${data.sop || ''}</p><h5 class="mt-4 font-semibold text-gray-900">Parts per Kit:</h5><ul class="mt-2 list-disc pl-5">${parts || '<li>No parts.</li>'}</ul>`);
        });
    });

    $('#close-sop-modal, #sop-modal').on('click', function (event) {
        if (event.target.id === 'close-sop-modal' || event.target.id === 'sop-modal') {
            $('#sop-modal').addClass('hidden').removeClass('flex');
        }
    });
</script>
@endpush
