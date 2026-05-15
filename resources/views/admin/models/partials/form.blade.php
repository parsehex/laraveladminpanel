@php
    $model = $model ?? null;
    $prefix = $prefix ?? 'model';
@endphp

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div>
        <label for="{{ $prefix }}-model-number" class="block text-sm font-medium text-gray-700 mb-2">Model # <span class="text-red-500">*</span></label>
        <input type="text" id="{{ $prefix }}-model-number" name="model_number" value="{{ old('model_number', $model?->model_number) }}" required
               placeholder="Model #"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('model_number')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-product-name" class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
        <input type="text" id="{{ $prefix }}-product-name" name="product_name" value="{{ old('product_name', $model?->product_name) }}"
               placeholder="Product Name"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('product_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-brand" class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
        <input type="text" id="{{ $prefix }}-brand" name="brand" value="{{ old('brand', $model?->brand) }}"
               placeholder="Brand"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('brand')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-msrp" class="block text-sm font-medium text-gray-700 mb-2">MSRP</label>
        <input type="number" id="{{ $prefix }}-msrp" name="msrp" value="{{ old('msrp', $model?->msrp ?? 0) }}"
               placeholder="MSRP" min="0" step="0.01"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('msrp')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
        <div class="flex gap-2">
            <select id="{{ $prefix }}-category" name="category_id" data-ajax-dropdown="category"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Category --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) old('category_id', $model?->category_id) === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @if(auth()->user()?->can('category.create') || auth()->user()?->can('models.create'))
            <button type="button" class="mt-1 h-8 w-8 flex-shrink-0 rounded-md bg-blue-600 text-xs text-white hover:bg-blue-700" title="Add category" data-open-quick-create="category" data-target="#{{ $prefix }}-category">
                <i class="fas fa-plus"></i>
            </button>
            @endif
        </div>
        @error('category_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
