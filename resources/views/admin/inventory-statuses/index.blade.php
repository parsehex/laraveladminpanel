@extends('layouts.admin')

@section('title', 'Inventory Statuses')
@section('page-title', 'Inventory Statuses')

@section('content')
@php
    $editableStatuses = $statuses->whereNull('archived_at');
@endphp
{{-- Empty form shell so row inputs/buttons can associate via form= without nesting action forms. --}}
<form id="statuses-bulk-form" method="POST" action="{{ route('admin.inventory-statuses.update') }}" class="hidden">
    @csrf
    @method('PATCH')
</form>

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

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-white">Statuses</h2>
                <span class="text-sm text-slate-200">{{ $statuses->count() }} statuses</span>
            </div>
            @if($editableStatuses->isNotEmpty())
                <button type="submit" form="statuses-bulk-form" class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-slate-800 hover:bg-slate-100">
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
                                       form="statuses-bulk-form"
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
                                       form="statuses-bulk-form"
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
                                    {{-- Single-quoted attr: @json emits double quotes --}}
                                    <form method="POST"
                                          action="{{ route('admin.inventory-statuses.apply-auto-location', $status) }}"
                                          onsubmit='return confirm(@json($status->apply_location_confirm));'>
                                        @csrf
                                        <button type="submit" class="inline-flex items-center rounded-md border border-emerald-300 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                                            Apply location to {{ $status->items_needing_location }}
                                        </button>
                                    </form>
                                @elseif($status->auto_location && $status->item_count > 0 && ! $status->archived_at)
                                    <span class="text-xs text-emerald-700">Locations current</span>
                                @endif

                                @if(! $status->is_system && $status->archived_at === null && $status->item_count === 0)
                                    <form method="POST"
                                          action="{{ route('admin.inventory-statuses.archive', $status) }}"
                                          onsubmit='return confirm(@json('Archive '.$status->name.'? It will no longer appear in status dropdowns.'));'>
                                        @csrf
                                        <button type="submit" class="text-amber-700 hover:text-amber-900 font-medium">
                                            Archive
                                        </button>
                                    </form>
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
                <button type="submit" form="statuses-bulk-form" class="inline-flex items-center gap-2 rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    <i class="fas fa-save"></i>
                    Save
                </button>
            </div>
        @endif
    </div>
</div>
@endsection
