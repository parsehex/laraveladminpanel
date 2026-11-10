@php
    $model = $model ?? null;
    $prefix = $prefix ?? 'model';
    $selectedCategoryId = old('category_id', $model?->category_id);
    $selectedCategory = $selectedCategoryId
        ? ($categories->firstWhere('id', (int) $selectedCategoryId) ?? $model?->category)
        : null;
    $itemType = old('item_type')
        ?: ($selectedCategory?->type?->value ?? \App\Enums\ItemType::Appliance->value);
    $itemTypeOptions = [
        'appliance' => ['label' => 'Appliance', 'icon' => 'fa-plug'],
        'furniture' => ['label' => 'Furniture', 'icon' => 'fa-couch'],
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-4 gap-6" data-model-form>
    <div>
        <label for="{{ $prefix }}-model-number" class="block text-sm font-medium text-gray-700 mb-2">Model # <span class="text-red-500">*</span></label>
        <input type="text" id="{{ $prefix }}-model-number" name="model_number" value="{{ old('model_number', $model?->model_number) }}" required
               placeholder="Model #"
               class="caps w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
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
        <div class="flex gap-2">
            <select id="{{ $prefix }}-brand" name="brand" data-ajax-dropdown="brand"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Brand --</option>
                @if(old('brand', $model?->brand))
                    <option value="{{ old('brand', $model?->brand) }}" selected>{{ old('brand', $model?->brand) }}</option>
                @endif
            </select>
            @if(auth()->user()?->can('models.create') || auth()->user()?->can('models.edit'))
            <button type="button" class="mt-1 h-8 w-8 flex-shrink-0 rounded-md bg-blue-600 text-xs text-white hover:bg-blue-700" title="Add brand" data-open-quick-create="brand" data-target="#{{ $prefix }}-brand">
                <i class="fas fa-plus"></i>
            </button>
            @endif
        </div>
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
        <label for="{{ $prefix }}-category" class="block text-sm font-medium text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
        <div class="flex gap-2">
            <div class="flex h-[42px] shrink-0 items-center gap-0.5 rounded-md border border-gray-300 bg-white p-0.5" role="group" aria-label="Item type">
                @foreach($itemTypeOptions as $typeValue => $typeOption)
                    <button type="button"
                            data-item-type-option="{{ $typeValue }}"
                            aria-pressed="{{ $itemType === $typeValue ? 'true' : 'false' }}"
                            aria-label="{{ $typeOption['label'] }} item"
                            class="ui-flyout-host inline-flex h-full w-9 items-center justify-center rounded text-gray-500 hover:bg-gray-100 aria-pressed:bg-blue-600 aria-pressed:text-white">
                        <i class="fas {{ $typeOption['icon'] }}"></i>
                        <span class="ui-flyout-tooltip ui-flyout-above" role="tooltip">{{ $typeOption['label'] }}</span>
                    </button>
                @endforeach
            </div>
            <input type="hidden" name="item_type" value="{{ $itemType }}" data-item-type-input>
            <select id="{{ $prefix }}-category" name="category_id" data-ajax-dropdown="category" data-item-type="{{ $itemType }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Category --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" data-type="{{ $category->type?->value ?? 'appliance' }}" @selected((string) $selectedCategoryId === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
                @if($selectedCategory && ! $categories->contains('id', (int) $selectedCategory->id))
                    <option value="{{ $selectedCategory->id }}" data-type="{{ $selectedCategory->type?->value ?? 'appliance' }}" selected>
                        {{ $selectedCategory->name }}
                    </option>
                @endif
            </select>
            @if(auth()->user()?->can('category.create'))
            <button type="button" class="mt-1 h-8 w-8 flex-shrink-0 rounded-md bg-blue-600 text-xs text-white hover:bg-blue-700" title="Add category" data-open-quick-create="category" data-target="#{{ $prefix }}-category">
                <i class="fas fa-plus"></i>
            </button>
            @endif
        </div>
        @error('category_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('item_type')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
