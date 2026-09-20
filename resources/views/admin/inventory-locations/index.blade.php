@extends('layouts.admin')

@section('title', 'Inventory Locations')
@section('page-title', 'Inventory Locations')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Locations in use</h2>
            <span class="text-sm text-slate-200">{{ $locations->count() }} labels</span>
        </div>
        <p class="px-6 py-4 text-sm text-gray-600 border-b border-gray-100">
            Locations are still typed on each item. This list is every distinct label currently stored — including case variants. Renaming a label updates every item that uses that exact string. If the new name already exists, those items are merged into it.
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
                    @forelse($locations as $location)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $location->location }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $location->item_count }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.inventory-locations.rename') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center"
                                  onsubmit="return confirm(@json('Rename '.$location->item_count.' item(s) from “'.$location->location.'”? If the new name already exists, they will be merged.'));">
                                @csrf
                                <input type="hidden" name="from" value="{{ $location->location }}">
                                <input type="text" name="to" value="{{ old('from') === $location->location ? old('to', $location->location) : $location->location }}" required maxlength="255"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <button type="submit" class="inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white hover:bg-blue-700" aria-label="Rename" title="Rename">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">No locations are in use yet. Type one on an item and it will show up here.</td>
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
