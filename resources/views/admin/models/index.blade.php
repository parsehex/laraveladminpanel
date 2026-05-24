@extends('layouts.admin')

@section('title', 'Models')
@section('page-title', 'Models')

@section('content')
@php
    $blankModel = new \App\Models\Model([
        'msrp' => 0,
    ]);
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-4xl font-semibold text-gray-900">Models Management</h1>
    </div>

    @canAccess('models.create')
    <div id="models-results" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Add New Model</h2>
        </div>
        <form method="POST" action="{{ route('admin.models.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_form" value="create">
            @include('admin.models.partials.form', ['model' => $blankModel, 'categories' => $categories, 'prefix' => 'create'])

            <div class="flex justify-end mt-6">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Add
                </button>
            </div>
        </form>
    </div>
    @endcanAccess

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-semibold text-white">Models List ({{ $models->total() }} total)</h2>
        </div>

        <div class="p-6 space-y-6">
            <form method="GET" action="{{ route('admin.models.index') }}" class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <label for="per_page" class="text-sm font-medium text-gray-700">Rows per page:</label>
                    <select id="per_page" name="per_page" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                    <span class="ml-auto text-sm text-gray-700">
                        {{ $models->firstItem() ?? 0 }} - {{ $models->lastItem() ?? 0 }} of {{ $models->total() }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Filter by Category:</label>
                        <div class="flex gap-2">
                            <select id="category" name="category" data-ajax-dropdown="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search by Model #:</label>
                        <input type="text" id="search" name="search" value="{{ request('search') }}"
                               placeholder="Search..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Filter</button>
                    <a href="{{ route('admin.models.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Reset</a>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-blue-500">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-white">Model #</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-white">Product Name</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-white">Brand</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-white">Category</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-white">MSRP</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-white">Variations</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($models as $model)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $model->model_number }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $model->product_name ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $model->brand ?: '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $model->category?->name ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${{ number_format((float) $model->msrp, 2) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $model->model_number }}-default</td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                @canAccess('models.view')
                                <button type="button" class="text-blue-600 hover:text-blue-900" title="View" data-toggle-row="model-view-{{ $model->id }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @endcanAccess
                                @canAccess('parts.view')
                                <button type="button" class="px-3 py-1 text-sm border border-blue-300 text-blue-600 rounded hover:bg-blue-50" data-toggle-row="model-parts-{{ $model->id }}">
                                    Parts ({{ $model->related_parts_count }})
                                </button>
                                @endcanAccess
                                @canAccess('models.edit')
                                <button type="button" class="px-3 py-1 text-sm border border-yellow-400 text-yellow-600 rounded hover:bg-yellow-50" data-toggle-row="model-edit-{{ $model->id }}">
                                    Edit
                                </button>
                                @endcanAccess
                                @canAccess('models.delete')
                                <form action="{{ route('admin.models.destroy', $model) }}" method="POST" class="inline" onsubmit="return confirm('Delete this model?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                                @endcanAccess
                            </td>
                        </tr>
                        <tr id="model-view-{{ $model->id }}" class="hidden bg-gray-50">
                            <td colspan="7" class="px-4 py-4">
                                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <dt class="font-medium text-gray-500">Model #</dt>
                                        <dd class="text-gray-900">{{ $model->model_number }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Product Name</dt>
                                        <dd class="text-gray-900">{{ $model->product_name ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Brand</dt>
                                        <dd class="text-gray-900">{{ $model->brand ?: '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Category</dt>
                                        <dd class="text-gray-900">{{ $model->category?->name ?? '-' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">MSRP</dt>
                                        <dd class="text-gray-900">${{ number_format((float) $model->msrp, 2) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-500">Variations</dt>
                                        <dd class="text-gray-900">{{ $model->model_number }}-default</dd>
                                    </div>
                                </dl>
                            </td>
                        </tr>
                        @canAccess('parts.view')
                        <tr id="model-parts-{{ $model->id }}" class="hidden bg-blue-50/50">
                            <td colspan="7" class="px-4 py-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">Related Parts for {{ $model->model_number }}</h3>
                                        <p class="text-sm text-gray-500">{{ $model->related_parts_count }} part{{ $model->related_parts_count === 1 ? '' : 's' }} linked by model compatibility.</p>
                                    </div>
                                    <a href="{{ route('admin.parts.index', ['search' => $model->model_number,'is_from_model_section' => true]) }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                        <i class="fas fa-search mr-2"></i>Open in Parts
                                    </a>
                                </div>

                                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Part #</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product Name</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Stock</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Retail</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Your Price</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Cross Reference</th>
                                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @forelse($model->relatedParts as $part)
                                                <tr>
                                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $part->part_number }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $part->product_name ?: '-' }}</td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700">{{ number_format((int) $part->total_stock) }}</td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700">${{ number_format((float) $part->retail_price, 2) }}</td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm text-gray-700">${{ number_format((float) $part->your_price, 2) }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $part->cross_reference ?: '-' }}</td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                                        <a href="{{ route('admin.parts.index', ['search' => $model->model_number, 'part_id' => $part->id]) }}" class="font-semibold text-blue-600 hover:text-blue-800">Open</a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">No related parts found for this model.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @endcanAccess
                        @canAccess('models.edit')
                        <tr id="model-edit-{{ $model->id }}" class="{{ $errors->any() && old('_form') === 'edit-'.$model->id ? '' : 'hidden' }} bg-gray-50">
                            <td colspan="7" class="px-4 py-4">
                                <form method="POST" action="{{ route('admin.models.update', $model) }}" class="space-y-6">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="_form" value="edit-{{ $model->id }}">
                                    @include('admin.models.partials.form', ['model' => $model, 'categories' => $categories, 'prefix' => 'edit-'.$model->id])

                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600" data-toggle-row="model-edit-{{ $model->id }}">Cancel</button>
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                            Update
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @endcanAccess
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">No models found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($models->hasPages())
            <div class="border-t border-gray-200 pt-4">{{ $models->links() }}</div>
            @endif
        </div>
    </div>
</div>

@include('admin.shared.ajax-dropdowns')
@endsection

@push('scripts')
<script>
    $('[data-toggle-row]').on('click', function () {
        const $row = $('#' + $(this).data('toggle-row')).toggleClass('hidden');
        if (! $row.hasClass('hidden')) {
            $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $row.find('input, select, textarea').filter(':visible:first').trigger('focus');
        }
    });

    const openModelForm = '{{ old('_form') }}';
    if (openModelForm && openModelForm.startsWith('edit-')) {
        const $row = $('#model-' + openModelForm);
        if ($row.length) {
            setTimeout(function () {
                $row[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                $row.find('input, select, textarea').filter(':visible:first').trigger('focus');
            }, 150);
        }
    }

    @if(request()->hasAny(['search', 'category']))
        setTimeout(function () {
            document.getElementById('models-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
    @endif
</script>
@endpush
