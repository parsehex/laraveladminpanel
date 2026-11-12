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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                <div>
                    <label for="status-color" class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="color" id="status-color" name="color" value="{{ old('color', '#6b7280') }}"
                           class="h-[42px] w-full cursor-pointer rounded-md border border-gray-300 bg-white px-1 py-1">
                    @error('color')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="text-sm text-gray-500">This location is applied the next time an item is set to this status. Existing items are not moved. Chip text color is chosen automatically for contrast.</p>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700">Create status</button>
            </div>
        </form>
    </div>

    @php
        $editableStatuses = $statuses->whereNull('archived_at');
    @endphp
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <form method="POST" action="{{ route('admin.inventory-statuses.update') }}" id="statuses-bulk-form">
            @csrf
            @method('PATCH')
            <div class="bg-slate-700 px-6 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-semibold text-white">Statuses</h2>
                    <span class="text-sm text-slate-200">{{ $statuses->count() }} statuses</span>
                </div>
                @if($editableStatuses->isNotEmpty())
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-100">
                        <i class="fas fa-save"></i>
                        Save
                    </button>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Color</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Items</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Auto-set location</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($statuses as $status)
                        <tr class="{{ $status->archived_at ? 'bg-slate-50 text-slate-500' : '' }}">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <div class="flex items-center gap-2">
                                    <x-admin.status-chip :status="$status->name" />
                                    @if($status->is_system)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">System</span>
                                    @endif
                                    @if($status->archived_at)
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Archived</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($status->archived_at)
                                    <span class="text-sm text-gray-500">{{ $status->color }}</span>
                                @else
                                    <input type="color"
                                           name="statuses[{{ $status->id }}][color]"
                                           value="{{ old('statuses.'.$status->id.'.color', $status->color) }}"
                                           class="h-[42px] w-16 cursor-pointer rounded-md border border-gray-300 bg-white px-1 py-1"
                                           aria-label="Color for {{ $status->name }}">
                                    @error('statuses.'.$status->id.'.color')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ $status->item_count }}</td>
                            <td class="px-4 py-3">
                                @if($status->archived_at)
                                    {{ $status->auto_location ?: '—' }}
                                @else
                                    <input type="text"
                                           name="statuses[{{ $status->id }}][auto_location]"
                                           value="{{ old('statuses.'.$status->id.'.auto_location', $status->auto_location) }}"
                                           maxlength="255"
                                           placeholder="None"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                           aria-label="Auto-set location for {{ $status->name }}">
                                    @error('statuses.'.$status->id.'.auto_location')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-col items-end gap-2">
                                    @if($status->auto_location && $status->items_needing_location > 0 && ! $status->archived_at)
                                        <button type="submit"
                                                form="apply-location-{{ $status->id }}"
                                                class="inline-flex items-center rounded-md border border-emerald-300 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100"
                                                onclick="return confirm(@json('Update '.$status->items_needing_location.' item(s) currently in '.$status->name.' to location “'.$status->auto_location.'”?'));">
                                            Apply location to {{ $status->items_needing_location }}
                                        </button>
                                    @elseif($status->auto_location && $status->item_count > 0 && ! $status->archived_at)
                                        <span class="text-xs text-emerald-700">Locations current</span>
                                    @endif

                                    @if(! $status->is_system && $status->archived_at === null && $status->item_count === 0)
                                        <button type="submit"
                                                form="archive-status-{{ $status->id }}"
                                                class="text-amber-700 hover:text-amber-900 font-medium"
                                                onclick="return confirm(@json('Archive '.$status->name.'? It will no longer appear in status dropdowns.'));">
                                            Archive
                                        </button>
                                    @elseif($status->archived_at)
                                        <span class="text-xs text-gray-400">Archived</span>
                                    @elseif(! $status->auto_location || $status->item_count === 0)
                                        <span class="text-xs text-gray-400">{{ $status->is_system ? 'Locked' : 'In use' }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No statuses found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @error('statuses')
                <p class="px-4 py-3 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('status')
                <p class="px-4 py-3 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @if($editableStatuses->isNotEmpty())
                <div class="flex justify-end border-t border-gray-200 px-6 py-4">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        <i class="fas fa-save"></i>
                        Save
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>

@foreach($statuses as $status)
    @if(! $status->archived_at && $status->auto_location && $status->items_needing_location > 0)
        <form id="apply-location-{{ $status->id }}" method="POST" action="{{ route('admin.inventory-statuses.apply-auto-location', $status) }}" class="hidden">
            @csrf
        </form>
    @endif
    @if(! $status->is_system && $status->archived_at === null && $status->item_count === 0)
        <form id="archive-status-{{ $status->id }}" method="POST" action="{{ route('admin.inventory-statuses.archive', $status) }}" class="hidden">
            @csrf
        </form>
    @endif
@endforeach
@endsection
