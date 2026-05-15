@extends('layouts.admin')

@section('title', 'Trucks')
@section('page-title', 'Trucks')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Trucks</h1>
        @canAccess('trucks.create')
        <a href="{{ route('admin.trucks.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i>Add truck
        </a>
        @endcanAccess
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.trucks.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search by name</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="Truck name…"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Filter</button>
                <a href="{{ route('admin.trucks.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total MSRP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrival</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created by</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($trucks as $truck)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $truck->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $truck->units_on_truck }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${{ number_format($truck->cost_of_truck, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${{ number_format($truck->total_appliance_msrp ?? 0, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $truck->arrival_date ? $truck->arrival_date : '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $truck->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($truck->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $truck->creator?->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            @canAccess('trucks.view')
                            <a href="{{ route('admin.trucks.show', $truck) }}" class="text-blue-600 hover:text-blue-900"><i class="fas fa-eye"></i></a>
                            @endcanAccess
                            @canAccess('trucks.edit')
                            <a href="{{ route('admin.trucks.edit', $truck) }}" class="text-green-600 hover:text-green-900"><i class="fas fa-edit"></i></a>
                            @endcanAccess
                            @canAccess('trucks.delete')
                            <form action="{{ route('admin.trucks.destroy', $truck) }}" method="POST" class="inline" onsubmit="return confirm('Delete this truck?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                            </form>
                            @endcanAccess
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">No trucks found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($trucks->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">{{ $trucks->links() }}</div>
        @endif
    </div>
</div>
@endsection
