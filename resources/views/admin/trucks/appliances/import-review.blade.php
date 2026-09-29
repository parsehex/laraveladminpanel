@extends('layouts.admin')

@section('title', 'Review Appliance Import')
@section('page-title', 'Review Appliance Import')
@section('page-subtitle', $truck->name)

@section('page-actions')
    <a href="{{ route('admin.trucks.show', $truck) }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
        Back to truck
    </a>
@endsection

@section('content')
<div class="flex flex-col gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 font-semibold text-green-800">
                {{ $preview->createCount() }} new
            </span>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 font-semibold text-blue-800">
                {{ $preview->updateCount() }} updates
            </span>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">
                {{ $preview->unchangedCount }} unchanged
            </span>
            <span class="inline-flex items-center rounded-full {{ $preview->errorCount() > 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700' }} px-3 py-1 font-semibold">
                {{ $preview->errorCount() }} errors
            </span>
        </div>
        <p class="mt-3 text-sm text-gray-600">
            Review the changes below. Matching uses unit label first, then serial number. Confirm only when there are no errors.
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.trucks.appliances.import.cancel', $truck) }}">
                @csrf
                <button type="submit" class="rounded-md bg-gray-500 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600">
                    Cancel
                </button>
            </form>
            @if($preview->canConfirm())
                <form method="POST" action="{{ route('admin.trucks.appliances.import.confirm', $truck) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        <i class="fas fa-check mr-1"></i>Confirm import
                    </button>
                </form>
            @else
                <button type="button" disabled class="cursor-not-allowed rounded-md bg-blue-300 px-4 py-2 text-sm font-semibold text-white">
                    <i class="fas fa-check mr-1"></i>Confirm import
                </button>
            @endif
        </div>
    </div>

    @if($preview->errors !== [])
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-red-700">Will skip / errors</h2>
                <p class="text-sm text-gray-500">Fix these rows in the CSV and upload again.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Row</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit Label</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Serial #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Errors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($preview->errors as $error)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $error['row_number'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $error['unit_label'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $error['serial_number'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-red-700">
                                    <ul class="list-disc pl-4">
                                        @foreach($error['errors'] as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($preview->creates !== [])
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Will create</h2>
                <p class="text-sm text-gray-500">{{ $preview->createCount() }} new appliance(s) on this truck.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit Label</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Brand</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Model</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Serial #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Our Cost</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">MSRP</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Receiving</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($preview->creates as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row['unit_label'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['category'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['brand'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['model'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['serial_number'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['status'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">${{ number_format((float) $row['price'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">${{ number_format((float) $row['msrp'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['receiving_condition'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @if($row['sold_price'] !== null || $row['sold_by'] || $row['sold_at'])
                                        ${{ number_format((float) ($row['sold_price'] ?? 0), 2) }}
                                        @if($row['sold_by']) · {{ $row['sold_by'] }}@endif
                                        @if($row['sold_at']) · {{ $row['sold_at'] }}@endif
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($preview->updates !== [])
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Will update</h2>
                <p class="text-sm text-gray-500">{{ $preview->updateCount() }} existing appliance(s) with field changes.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Matched As</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit Label</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Serial #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Changes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($preview->updates as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $row['match_key'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['unit_label'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['serial_number'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <ul class="space-y-1">
                                        @foreach($row['changes'] as $change)
                                            <li>
                                                <span class="font-medium text-gray-800">{{ $change['label'] }}:</span>
                                                <span class="text-gray-500">{{ $change['from'] }}</span>
                                                <span class="text-gray-400">→</span>
                                                <span class="rounded bg-amber-50 px-1 font-semibold text-amber-900">{{ $change['to'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($preview->hasSideEffects())
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Catalog side effects</h2>
                <p class="text-sm text-gray-500">These catalog records do not exist yet and will be created on confirm.</p>
            </div>
            <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
                @if($preview->newCategories !== [])
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Categories</h3>
                        <ul class="mt-2 list-disc pl-4 text-sm text-gray-700">
                            @foreach($preview->newCategories as $name)
                                <li>{{ $name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($preview->newSubcategories !== [])
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Subcategories</h3>
                        <ul class="mt-2 list-disc pl-4 text-sm text-gray-700">
                            @foreach($preview->newSubcategories as $name)
                                <li>{{ $name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($preview->newBrands !== [])
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Brands</h3>
                        <ul class="mt-2 list-disc pl-4 text-sm text-gray-700">
                            @foreach($preview->newBrands as $name)
                                <li>{{ $name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if($preview->newModels !== [])
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Models</h3>
                        <ul class="mt-2 list-disc pl-4 text-sm text-gray-700">
                            @foreach($preview->newModels as $name)
                                <li>{{ $name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($preview->creates === [] && $preview->updates === [] && $preview->errors === [])
        <div class="bg-white rounded-lg shadow p-6 text-sm text-gray-600">
            No create or update changes found in this CSV.
            @if($preview->unchangedCount > 0)
                {{ $preview->unchangedCount }} row(s) matched existing appliances with no field changes.
            @endif
        </div>
    @endif
</div>
@endsection
