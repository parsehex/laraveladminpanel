@extends('layouts.admin')

@section('title', 'Inventory Statuses')
@section('page-title', 'Inventory Statuses')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Add status</h2>
        </div>
        <form method="POST" action="{{ route('admin.inventory-statuses.store') }}" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="status-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" id="status-name" name="name" value="{{ old('name') }}" required maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="status-auto-location" class="block text-sm font-medium text-gray-700 mb-1">Auto-set location (optional)</label>
                    <input type="text" id="status-auto-location" name="auto_location" value="{{ old('auto_location') }}" maxlength="255"
                           placeholder="Leave blank to keep the item's location"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    @error('auto_location')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="text-sm text-gray-500">This location is applied the next time an item is set to this status. Existing items are not moved.</p>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700">Create status</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Statuses</h2>
            <span class="text-sm text-slate-200">{{ $statuses->count() }} statuses</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Auto-set location</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($statuses as $status)
                    <tr class="{{ $status->archived_at ? 'bg-slate-50 text-slate-500' : '' }}">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $status->name }}
                            @if($status->is_system)
                                <span class="ml-2 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">System</span>
                            @endif
                            @if($status->archived_at)
                                <span class="ml-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Archived</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $status->item_count }}</td>
                        <td class="px-4 py-3">
                            @if($status->archived_at)
                                {{ $status->auto_location ?: '—' }}
                            @else
                                <form method="POST" action="{{ route('admin.inventory-statuses.update', $status) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="auto_location" value="{{ old('auto_location', $status->auto_location) }}" maxlength="255"
                                           placeholder="None"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <button type="submit" class="px-3 py-2 rounded-md bg-slate-700 text-white text-sm font-semibold hover:bg-slate-800">Save</button>
                                </form>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(! $status->is_system && $status->archived_at === null && $status->item_count === 0)
                                <form method="POST" action="{{ route('admin.inventory-statuses.archive', $status) }}"
                                      onsubmit="return confirm(@json('Archive '.$status->name.'? It will no longer appear in status dropdowns.'));">
                                    @csrf
                                    <button type="submit" class="text-amber-700 hover:text-amber-900 font-medium">Archive</button>
                                </form>
                            @elseif($status->is_system)
                                <span class="text-xs text-gray-400">Locked</span>
                            @elseif($status->archived_at)
                                <span class="text-xs text-gray-400">Archived</span>
                            @else
                                <span class="text-xs text-gray-400">In use</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No statuses found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @error('status')
            <p class="px-4 py-3 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
@endsection
