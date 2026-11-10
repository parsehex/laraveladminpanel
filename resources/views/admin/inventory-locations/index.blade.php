@extends('layouts.admin')

@section('title', 'Inventory Locations')
@section('page-title', 'Inventory Locations')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Add location</h2>
        </div>
        <form method="POST" action="{{ route('admin.inventory-locations.store') }}" class="flex flex-col gap-3 p-6 sm:flex-row sm:items-end">
            @csrf
            <div class="min-w-0 flex-1">
                <label for="location-name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                <input id="location-name" type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                       placeholder="e.g. Inventory"
                       class="w-full rounded-md border border-gray-300 px-3 py-2">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="inline-flex h-[42px] items-center justify-center rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                Add
            </button>
        </form>
        <p class="border-t border-gray-100 px-6 py-3 text-sm text-gray-600">
            Catalog locations show up in location pickers even when no items use them yet. Items still store a free-text location; status auto-locations (SOLD, SHOWROOM, Scrap, …) are not added here and keep working as they do now.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Catalog</h2>
            <span class="text-sm text-slate-200">{{ count($catalog) }} locations</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rename to</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($catalog as $location)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $location['name'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $location['item_count'] }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.inventory-locations.rename') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center"
                                  onsubmit="return confirm(@json('Rename “'.$location['name'].'”? Items using that exact label will be updated. If the new name already exists, they will be merged.'));">
                                @csrf
                                <input type="hidden" name="from" value="{{ $location['name'] }}">
                                <input type="text" name="to" value="{{ old('from') === $location['name'] ? old('to', $location['name']) : $location['name'] }}" required maxlength="255"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <button type="submit" class="inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white hover:bg-blue-700" aria-label="Rename" title="Rename">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">No catalog locations yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">In use, not in catalog</h2>
            <span class="text-sm text-slate-200">{{ count($uncatalogued) }} labels</span>
        </div>
        <p class="px-6 py-4 text-sm text-gray-600 border-b border-gray-100">
            These labels are typed on items (including status auto-locations) but are not managed catalog entries. Rename/merge them here, or add the same name above to promote one into the catalog.
        </p>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rename to</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($uncatalogued as $location)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $location['name'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $location['item_count'] }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.inventory-locations.rename') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center"
                                  onsubmit="return confirm(@json('Rename '.$location['item_count'].' item(s) from “'.$location['name'].'”? If the new name already exists, they will be merged.'));">
                                @csrf
                                <input type="hidden" name="from" value="{{ $location['name'] }}">
                                <input type="text" name="to" value="{{ old('from') === $location['name'] ? old('to', $location['name']) : $location['name'] }}" required maxlength="255"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <button type="submit" class="inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white hover:bg-blue-700" aria-label="Rename" title="Rename">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">Every in-use label is in the catalog.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @error('from')
            <p class="px-4 py-3 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('to')
            <p class="px-4 py-3 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
@endsection
