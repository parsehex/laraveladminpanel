@extends('layouts.admin')

@section('title', 'Parts')
@section('page-title', 'Parts')

@section('page-actions')
    @canAccess('parts.create')
    <button type="button" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700" data-toggle-create>
        <i class="fas fa-plus mr-2"></i>Add part
    </button>
    @endcanAccess
@endsection

@section('content')
@php
    $blankPart = new \App\Models\Part([
        'total_stock' => 0,
        'retail_price' => 0,
        'your_price' => 0,
    ]);
@endphp

<div class="space-y-6">
    @canAccess('parts.create')
    <div id="create-part-panel" class="bg-white rounded-lg shadow p-6 {{ $errors->any() && old('_form') === 'create' ? '' : 'hidden' }}">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Add part</h2>
        <form method="POST" action="{{ route('admin.parts.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_form" value="create">
            @include('admin.parts.partials.form', ['part' => $blankPart, 'models' => $models, 'prefix' => 'create'])

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
        <form method="GET" action="{{ route('admin.parts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request('direction'))
                <input type="hidden" name="direction" value="{{ request('direction') }}">
            @endif
            <div class="md:col-span-3">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{request('is_from_model_section') ? "Search Model Compatibility" : "Search by any feild"}}</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}"
                       placeholder="{{request('is_from_model_section') ? 'Search Model Compatibility' : 'Search by any feild'}}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Filter</button>
                <a href="{{ route('admin.parts.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Reset</a>
            </div>
        </form>
        @canAccess('parts.create')
        <form method="POST" action="{{ route('admin.parts.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3 border-t border-gray-200 pt-4">
            @csrf
            <div>
                <label for="parts-csv" class="block text-sm font-medium text-gray-700 mb-1">CSV Upload</label>
                <input id="parts-csv" type="file" name="csv_file" accept=".csv,text/csv" required class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 max-w-3xl text-xs text-gray-500">Expected columns: URL (ignored), Part Number, Product Name, Retail Price, Your Price, Images (ignored), Cross Reference Information, Models it applies to. Header row skipped.</p>
            </div>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Upload CSV</button>
            <a href="{{ asset('examples/parts-import-example.csv') }}" class="rounded-md bg-gray-600 px-4 py-2 text-white hover:bg-gray-700" download>Example CSV</a>
        </form>
        @endcanAccess
    </div>

    <x-admin.data-table id="parts-results" :table="$dataTable">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky-table-head">
                    <tr>
                        <x-admin.data-table.header-cells :data-table="$dataTable" :sort="$sort" :direction="$direction" />
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($parts as $part)
                    <tr class="hover:bg-gray-50">
                        <x-admin.data-table.cell column="id">{{ $part->id }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="total_stock" align="right">{{ $part->total_stock }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="part_number" class="font-medium text-gray-900">{{ $part->part_number }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="product_name" truncate title="{{ $part->product_name ?: '-' }}">{{ $part->product_name ?: '-' }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="model_compatibility" truncate title="{{ $part->model_compatibility ?: '-' }}">{{ $part->model_compatibility ?: '-' }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="retail_price" align="right">${{ number_format($part->retail_price, 2) }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="your_price" align="right">${{ number_format($part->your_price, 2) }}</x-admin.data-table.cell>
                        <x-admin.data-table.cell column="cross_reference" truncate title="{{ $part->cross_reference ?: '-' }}">{{ $part->cross_reference ?: '-' }}</x-admin.data-table.cell>
                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            @canAccess('parts.view')
                            <button type="button" class="text-blue-600 hover:text-blue-900" title="View" data-toggle-row="part-view-{{ $part->id }}">
                                <i class="fas fa-eye"></i>
                            </button>
                            @endcanAccess
                            @canAccess('parts.edit')
                            <button type="button" class="text-green-600 hover:text-green-900" title="Edit" data-toggle-row="part-edit-{{ $part->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcanAccess
                            @canAccess('parts.delete')
                            <form action="{{ route('admin.parts.destroy', $part) }}" method="POST" class="inline" onsubmit="return confirm('Delete this part?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            @endcanAccess
                        </td>
                    </tr>
                    <tr id="part-view-{{ $part->id }}" class="hidden bg-gray-50">
                        <td colspan="9" class="p-0 align-top">
                            <div data-table-inline-panel class="bg-gray-50 px-6 py-4">
                                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <dt class="font-medium text-gray-500">Part #</dt>
                                        <dd class="text-gray-900">{{ $part->part_number }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Total Stock</dt>
                                        <dd class="text-gray-900">{{ $part->total_stock }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Cross Reference</dt>
                                        <dd class="text-gray-900">{{ $part->cross_reference ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Product Name</dt>
                                        <dd class="text-gray-900">{{ $part->product_name ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Model Compatibility</dt>
                                        <dd class="text-gray-900">{{ $part->model_compatibility ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Updated</dt>
                                        <dd class="text-gray-900">{{ $part->updated_at?->format('M j, Y g:i A') }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </td>
                    </tr>
                    @canAccess('parts.edit')
                    <tr id="part-edit-{{ $part->id }}" class="{{ $errors->any() && old('_form') === 'edit-'.$part->id ? '' : 'hidden' }} bg-gray-50">
                        <td colspan="9" class="p-0 align-top">
                            <div data-table-inline-panel class="bg-gray-50 px-6 py-4">
                                <form method="POST" action="{{ route('admin.parts.update', $part) }}" class="space-y-6">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="_form" value="edit-{{ $part->id }}">
                                    @include('admin.parts.partials.form', ['part' => $part, 'models' => $models, 'prefix' => 'edit-'.$part->id])

                                    <div class="flex justify-start gap-2">
                                        <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600" data-toggle-row="part-edit-{{ $part->id }}">Cancel</button>
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                            <i class="fas fa-save mr-2"></i>Update
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endcanAccess
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">No parts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        <x-slot:footer>
        <x-admin.table-pagination :paginator="$parts" />
        </x-slot:footer>
    </x-admin.data-table>
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
            if (typeof syncTableInlinePanels === 'function') {
                syncTableInlinePanels($row.closest('[data-wide-table], .overflow-x-auto').get(0) || document);
            }
            $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $row.find('input, select, textarea').filter(':visible:first').trigger('focus');
        }
    });

    @if(request()->hasAny(['search', 'part_id']))
        setTimeout(function () {
            const partId = '{{ request('part_id') }}';
            const target = partId ? document.getElementById('part-view-' + partId) : document.getElementById('parts-results');
            if (partId && target) {
                target.classList.remove('hidden');
            }
            (target || document.getElementById('parts-results'))?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
    @endif
</script>
@endpush
