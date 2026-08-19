@extends('layouts.admin')

@section('title', 'Deliveries')
@section('page-title', 'Deliveries')

@section('content')
<div class="space-y-6">
    @canAccess('deliveries.create')
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Add New Delivery</h2>
        </div>
        <form method="POST" action="{{ route('admin.deliveries.store') }}" class="p-6 space-y-6">
            @csrf

            <div>
                <h3 class="text-base font-semibold text-gray-900">Customer Information</h3>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                        <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label for="customer_number" class="block text-sm font-medium text-gray-700 mb-1">Customer Number</label>
                        <input id="customer_number" name="customer_number" value="{{ old('customer_number') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="customer_address" class="block text-sm font-medium text-gray-700 mb-1">Customer Address</label>
                        <textarea id="customer_address" name="customer_address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>{{ old('customer_address') }}</textarea>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-base font-semibold text-gray-900">Delivery Details</h3>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="order_appliances" class="block text-sm font-medium text-gray-700 mb-1">Order (Appliances)</label>
                        <textarea id="order_appliances" name="order_appliances" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>{{ old('order_appliances') }}</textarea>
                    </div>
                    <div>
                        <label for="delivery_fee" class="block text-sm font-medium text-gray-700 mb-1">Delivery Fee</label>
                        <input id="delivery_fee" type="number" step="0.01" min="0" name="delivery_fee" value="{{ old('delivery_fee') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="delivery_timeframe" class="block text-sm font-medium text-gray-700 mb-1">Delivery Time Frame</label>
                        <input id="delivery_timeframe" name="delivery_timeframe" value="{{ old('delivery_timeframe') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="delivery_type" class="block text-sm font-medium text-gray-700 mb-1">Install or Drop Off</label>
                        <select id="delivery_type" name="delivery_type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="Install" @selected(old('delivery_type', 'Install') === 'Install')>Install</option>
                            <option value="Drop Off" @selected(old('delivery_type') === 'Drop Off')>Drop Off</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:col-span-2">
                        <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-3 text-sm font-semibold text-gray-800">
                            <input type="checkbox" name="haul_away" value="1" @checked(old('haul_away'))>
                            Haul Away Old Appliances
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-md border border-gray-200 px-3 py-3 text-sm font-semibold text-gray-800">
                            <input type="checkbox" name="collect_payment" value="1" @checked(old('collect_payment'))>
                            Collect Payment Upon Delivery
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="reset" class="px-4 py-2 rounded-md bg-gray-500 text-white font-semibold">Reset</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold">Add Delivery</button>
            </div>
        </form>
    </div>
    @endcanAccess

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Deliveries List</h2>
        </div>
        <div class="p-4">
            <form method="GET" action="{{ route('admin.deliveries.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                <input type="text" name="search" value="{{ request('search') }}" class="px-3 py-2 border border-gray-300 rounded-md" placeholder="Search customer or phone">
                <select name="limit" class="px-3 py-2 border border-gray-300 rounded-md">
                    @foreach([25, 50, 100, 250, 500, 1000] as $size)
                        <option value="{{ $size }}" @selected((string) request('limit', 25) === (string) $size)>{{ $size }}</option>
                    @endforeach
                    <option value="all" @selected(request('limit') === 'all')>All</option>
                </select>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-md">Filter</button>
                <a href="{{ route('admin.deliveries.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-md text-center">Reset</a>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Address</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Order</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Fee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Time Frame</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Haul Away</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Collect Payment</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($deliveries as $delivery)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $delivery->customer_name }}</td>
                                <td class="px-4 py-3">{{ $delivery->customer_number }}</td>
                                <td class="px-4 py-3 max-w-xs whitespace-pre-line">{{ $delivery->customer_address }}</td>
                                <td class="px-4 py-3 max-w-xs whitespace-pre-line">{{ $delivery->order_appliances }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format((float) $delivery->delivery_fee, 2) }}</td>
                                <td class="px-4 py-3">{{ $delivery->delivery_timeframe ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $delivery->delivery_type }}</td>
                                <td class="px-4 py-3">{{ $delivery->haul_away ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3">{{ $delivery->collect_payment ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @canAccess('deliveries.delete')
                                    <form method="POST" action="{{ route('admin.deliveries.destroy', $delivery) }}" onsubmit="return confirm('Delete this delivery?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 font-semibold hover:text-red-800">Delete</button>
                                    </form>
                                    @endcanAccess
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">No deliveries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500">
                    Showing {{ $deliveries->firstItem() ?? 0 }} - {{ $deliveries->lastItem() ?? 0 }} of {{ $totalRecords }}
                </p>
                <div>{{ $deliveries->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
