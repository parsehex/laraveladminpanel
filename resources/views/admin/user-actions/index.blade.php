@extends('layouts.admin')

@section('title', 'User Actions')
@section('page-title', 'User Actions')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.user-actions.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Search by user or action…"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="per_page" class="block text-sm font-medium text-gray-700 mb-1">Per page</label>
                <select id="per_page" name="per_page" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach([25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
                <a href="{{ route('admin.user-actions.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-undo mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Extra</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($actions as $action)
                        <tr class="hover:bg-gray-50" x-data="{ showExtra: false }">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $action->created_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $action->username ?: '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                {{ $action->action_type }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($action->item_id)
                                    @canAccess('inventory.view')
                                        <a href="{{ route('admin.inventory.show', $action->item_id) }}" class="text-blue-600 hover:text-blue-800">
                                            Item #{{ $action->item_id }}
                                            @if($action->item?->model?->model_number)
                                                <span class="block text-xs text-gray-500">({{ $action->item->model->model_number }})</span>
                                            @endif
                                        </a>
                                    @else
                                        Item #{{ $action->item_id }}
                                    @endcanAccess
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($action->extra)
                                    <button type="button" @click="showExtra = !showExtra" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                        <span x-text="showExtra ? 'Hide' : 'Show'"></span> JSON
                                    </button>
                                    <pre x-show="showExtra" x-cloak class="mt-2 max-w-md overflow-x-auto rounded bg-gray-50 p-2 text-xs text-gray-700">{{ json_encode($action->extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                No user actions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($actions->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $actions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
