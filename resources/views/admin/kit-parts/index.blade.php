@extends('layouts.admin')

@section('title', 'Kit Parts')
@section('page-title', 'Kit Parts')

@section('page-actions')
    @canAccess('kit-parts.create')
    <button type="button" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700" data-toggle-create>
        <i class="fas fa-plus mr-2"></i>Add kit part
    </button>
    @endcanAccess
@endsection

@section('content')
@php
    $blankPart = new \App\Models\KitCatalogPart([
        'total_stock' => 0,
        'retail_price' => 0,
        'your_price' => 0,
    ]);
@endphp

<div class="space-y-6">
    @canAccess('kit-parts.create')
    <div id="create-part-panel" class="bg-white rounded-lg shadow p-6 {{ $errors->any() && old('_form') === 'create' ? '' : 'hidden' }}">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Add kit part</h2>
        <form method="POST" action="{{ route('admin.kit-parts.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_form" value="create">
            @include('admin.parts.partials.form', ['part' => $blankPart, 'models' => $models, 'prefix' => 'create-kit-part'])

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600" data-toggle-create>Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
            </div>
        </form>
    </div>
    @endcanAccess

    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <form method="GET" action="{{ route('admin.kit-parts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-3">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search by any field</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="Search by any field"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Filter</button>
                <a href="{{ route('admin.kit-parts.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Reset</a>
            </div>
        </form>
        @canAccess('kit-parts.create')
        <form method="POST" action="{{ route('admin.kit-parts.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4">
            @csrf
            <div>
                <label for="kit-parts-csv" class="block text-sm font-medium text-gray-700 mb-1">CSV Upload</label>
                <input id="kit-parts-csv" type="file" name="csv_file" accept=".csv,text/csv" required class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 max-w-3xl text-xs text-gray-500">Expected columns: URL (ignored), Part Number, Product Name, Retail Price, Your Price, Images (ignored), Cross Reference Information, Models it applies to.</p>
            </div>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Upload CSV</button>
        </form>
        @endcanAccess
    </div>

    <div id="parts-results" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sr. No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Part #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model Compatibility</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retail Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cross Reference</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($parts as $part)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $part->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $part->total_stock }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $part->part_number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $part->product_name ?: '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $part->model_compatibility ?: '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${{ number_format($part->retail_price, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${{ number_format($part->your_price, 2) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $part->cross_reference ?: '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            @canAccess('kit-parts.view')
                            <button type="button" class="text-blue-600 hover:text-blue-900" title="View" data-toggle-row="part-view-{{ $part->id }}">
                                <i class="fas fa-eye"></i>
                            </button>
                            @endcanAccess
                            @canAccess('kit-parts.edit')
                            <button type="button" class="text-green-600 hover:text-green-900" title="Edit" data-toggle-row="part-edit-{{ $part->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcanAccess
                            @canAccess('kit-parts.delete')
                            <form action="{{ route('admin.kit-parts.destroy', $part) }}" method="POST" class="inline" onsubmit="return confirm('Delete this kit part?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            @endcanAccess
                        </td>
                    </tr>
                    <tr id="part-view-{{ $part->id }}" class="hidden bg-gray-50">
                        <td colspan="9" class="px-6 py-4">
                            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                <div><dt class="font-medium text-gray-500">Part #</dt><dd class="text-gray-900">{{ $part->part_number }}</dd></div>
                                <div><dt class="font-medium text-gray-500">Total Stock</dt><dd class="text-gray-900">{{ $part->total_stock }}</dd></div>
                                <div><dt class="font-medium text-gray-500">Cross Reference</dt><dd class="text-gray-900">{{ $part->cross_reference ?: '-' }}</dd></div>
                                <div><dt class="font-medium text-gray-500">Product Name</dt><dd class="text-gray-900">{{ $part->product_name ?: '-' }}</dd></div>
                                <div><dt class="font-medium text-gray-500">Model Compatibility</dt><dd class="text-gray-900">{{ $part->model_compatibility ?: '-' }}</dd></div>
                                <div><dt class="font-medium text-gray-500">Updated</dt><dd class="text-gray-900">{{ $part->updated_at?->format('M j, Y g:i A') }}</dd></div>
                            </dl>
                        </td>
                    </tr>
                    @canAccess('kit-parts.edit')
                    <tr id="part-edit-{{ $part->id }}" class="{{ $errors->any() && old('_form') === 'edit-'.$part->id ? '' : 'hidden' }} bg-gray-50">
                        <td colspan="9" class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.kit-parts.update', $part) }}" class="space-y-6">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_form" value="edit-{{ $part->id }}">
                                @include('admin.parts.partials.form', ['part' => $part, 'models' => $models, 'prefix' => 'edit-kit-part-'.$part->id])

                                <div class="flex justify-end gap-2">
                                    <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600" data-toggle-row="part-edit-{{ $part->id }}">Cancel</button>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        <i class="fas fa-save mr-2"></i>Update
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endcanAccess
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">No kit parts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($parts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">{{ $parts->links() }}</div>
        @endif
    </div>
</div>

@include('admin.shared.ajax-dropdowns')
@endsection

@push('scripts')
<script>
    $('[data-toggle-create]').on('click', function () {
        const $panel = $('#create-part-panel').toggleClass('hidden');
        if (! $panel.hasClass('hidden')) {
            $panel[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            $panel.find('input, select, textarea').filter(':visible:first').trigger('focus');
        }
    });

    $('[data-toggle-row]').on('click', function () {
        const $row = $('#' + $(this).data('toggle-row')).toggleClass('hidden');
        if (! $row.hasClass('hidden')) {
            $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $row.find('input, select, textarea').filter(':visible:first').trigger('focus');
        }
    });
</script>
@endpush
