@php($appearance = $appearance ?? 'legacy')

@if($appearance === 'card')
<div class="bg-white rounded-lg shadow overflow-hidden" id="parts-panel">
    <div class="bg-slate-800 px-5 py-3">
        <h2 class="text-lg font-semibold text-white">Parts used</h2>
    </div>
    <div class="p-5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500">Description</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500">Cost</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500">Source / Part #</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500">Added</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($appliance->parts->sortByDesc('created_at') as $part)
                    <tr>
                        <td class="px-3 py-2 text-gray-900">{{ $part->description }}</td>
                        <td class="px-3 py-2 text-gray-700">${{ number_format($part->cost, 2) }}</td>
                        <td class="px-3 py-2 text-gray-700">{{ $part->part?->part_number ?: ($part->part_number ?: ($part->source ?: '—')) }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $part->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2">
                            @canAccess('appliance.edit')
                            <form method="POST" action="{{ route('admin.inventory.parts.destroy', [$appliance, $part]) }}" onsubmit="return confirm('Remove this part?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 underline text-sm">Delete</button>
                            </form>
                            @else
                            —
                            @endcanAccess
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-3 py-6 text-center text-gray-500">No parts added yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@canAccess('appliance.edit')
<div class="bg-white rounded-lg shadow overflow-hidden" id="add-part-form">
    <div class="bg-amber-500 px-5 py-3">
        <h2 class="text-lg font-semibold text-white">Add part</h2>
    </div>
    <div class="p-5">
        <form method="POST" action="{{ route('admin.inventory.parts.store', $appliance) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4" id="add-part-form-fields">
            @csrf
            <input type="hidden" name="part_id" id="selected-part-id" value="{{ old('part_id') }}">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Part description</label>
                <div class="relative">
                    <input name="description" id="part-description-input" value="{{ old('description') }}" placeholder="Search or enter manually…" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" autocomplete="off" required>
                    <div id="part-search-results" class="hidden absolute z-30 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg max-h-56 overflow-y-auto"></div>
                </div>
                <p class="mt-1 text-xs text-gray-500">Start typing to search inventory</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cost ($)</label>
                <input type="number" step="0.01" min="0" name="cost" id="part-cost-input" value="{{ old('cost', 0) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Source / Part #</label>
                <input id="part-number-preview" value="Auto-generated after save" class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-600" readonly>
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="rounded-md bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Add part</button>
            </div>
        </form>
    </div>
</div>
@endcanAccess

@else
<section class="legacy-panel" id="parts-panel">
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
<section class="legacy-panel" id="add-part-form">
    <div class="legacy-panel-heading bg-yellow-400 text-black">Add Part</div>
    <div class="legacy-panel-body">
        <form method="POST" action="{{ route('admin.inventory.parts.store', $appliance) }}" class="grid grid-cols-1 md:grid-cols-3 gap-2" id="add-part-form-fields">
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
@endif
