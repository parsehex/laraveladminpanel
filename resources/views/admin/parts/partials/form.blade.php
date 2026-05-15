@php
    $part = $part ?? null;
    $models = $models ?? collect();
    $prefix = $prefix ?? 'part';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="{{ $prefix }}-part-number" class="block text-sm font-medium text-gray-700 mb-2">Part # <span class="text-red-500">*</span></label>
        <input type="text" id="{{ $prefix }}-part-number" name="part_number" value="{{ old('part_number', $part?->part_number) }}" required
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('part_number')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-product-name" class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
        <input type="text" id="{{ $prefix }}-product-name" name="product_name" value="{{ old('product_name', $part?->product_name) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('product_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-model-compatibility" class="block text-sm font-medium text-gray-700 mb-2">Model Compatibility</label>
        <div class="flex gap-2">
            <select id="{{ $prefix }}-model-compatibility" name="model_compatibility" data-ajax-dropdown="model"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Model --</option>
                @foreach($models as $model)
                    <option value="{{ $model->model_number }}" {{ old('model_compatibility', $part?->model_compatibility) === $model->model_number ? 'selected' : '' }}>
                        {{ $model->model_number }}{{ $model->product_name ? ' - '.$model->product_name : '' }}
                    </option>
                @endforeach
            </select>
            @canAccess('models.create')
            <button type="button" class="mt-1 h-8 w-8 flex-shrink-0 rounded-md bg-blue-600 text-xs text-white hover:bg-blue-700" title="Add model" data-open-quick-create="model" data-target="#{{ $prefix }}-model-compatibility">
                <i class="fas fa-plus"></i>
            </button>
            @endcanAccess
        </div>
        @error('model_compatibility')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-total-stock" class="block text-sm font-medium text-gray-700 mb-2">Total Stock <span class="text-red-500">*</span></label>
        <input type="number" id="{{ $prefix }}-total-stock" name="total_stock" value="{{ old('total_stock', $part?->total_stock ?? 0) }}" min="0" required
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('total_stock')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-retail-price" class="block text-sm font-medium text-gray-700 mb-2">Retail Price <span class="text-red-500">*</span></label>
        <input type="number" id="{{ $prefix }}-retail-price" name="retail_price" value="{{ old('retail_price', $part?->retail_price ?? 0) }}" min="0" step="0.01" required
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('retail_price')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-your-price" class="block text-sm font-medium text-gray-700 mb-2">Your Price <span class="text-red-500">*</span></label>
        <input type="number" id="{{ $prefix }}-your-price" name="your_price" value="{{ old('your_price', $part?->your_price ?? 0) }}" min="0" step="0.01" required
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('your_price')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="{{ $prefix }}-cross-reference" class="block text-sm font-medium text-gray-700 mb-2">Cross Reference</label>
        <input type="text" id="{{ $prefix }}-cross-reference" name="cross_reference" value="{{ old('cross_reference', $part?->cross_reference) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('cross_reference')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
