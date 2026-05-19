@php
    $appliance = $appliance ?? null;
    $prefix = $prefix ?? 'appliance';
    $categories = $categories ?? collect();
    $models = $models ?? collect();
    $conditions = \App\Models\TruckAppliance::RECEIVING_CONDITIONS;
@endphp

<input type="hidden" name="truck_id" value="{{ $truck->id }}">

<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div>
        <label for="{{ $prefix }}-category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
        <div class="flex gap-2">
            <select id="{{ $prefix }}-category" name="category_id" data-ajax-dropdown="category"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Category --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) old('category_id', $appliance?->category_id) === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
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
    </div>

    <div>
        <label for="{{ $prefix }}-model" class="block text-sm font-medium text-gray-700 mb-2">Model</label>
        <div class="flex gap-2">
            <select id="{{ $prefix }}-model" name="model_id" data-ajax-dropdown="model" data-value-field="id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Model --</option>
                @foreach($models as $model)
                    <option value="{{ $model->id }}" {{ (string) old('model_id', $appliance?->model_id) === (string) $model->id ? 'selected' : '' }}>
                        {{ $model->model_number }}{{ $model->product_name ? ' - '.$model->product_name : '' }}
                    </option>
                @endforeach
            </select>
            @canAccess('models.create')
            <button type="button" class="mt-1 h-8 w-8 flex-shrink-0 rounded-md bg-blue-600 text-xs text-white hover:bg-blue-700" title="Add model" data-open-quick-create="model" data-target="#{{ $prefix }}-model">
                <i class="fas fa-plus"></i>
            </button>
            @endcanAccess
        </div>
        @error('model_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-serial-number" class="block text-sm font-medium text-gray-700 mb-2">Serial #</label>
        <input type="text" id="{{ $prefix }}-serial-number" name="serial_number" value="{{ old('serial_number', $appliance?->serial_number) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('serial_number')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-brand" class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
        <div class="flex gap-2">
            <select id="{{ $prefix }}-brand" name="brand" data-ajax-dropdown="brand"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Select Brand --</option>
                @if(old('brand', $appliance?->brand))
                    <option value="{{ old('brand', $appliance?->brand) }}" selected>{{ old('brand', $appliance?->brand) }}</option>
                @endif
            </select>
            @if(auth()->user()?->can('appliance.create') || auth()->user()?->can('appliance.edit'))
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
        <label for="{{ $prefix }}-product-name" class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
        <input type="text" id="{{ $prefix }}-product-name" name="product_name" value="{{ old('product_name', $appliance?->product_name) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('product_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-msrp" class="block text-sm font-medium text-gray-700 mb-2">MSRP</label>
        <input type="number" id="{{ $prefix }}-msrp" name="msrp" value="{{ old('msrp', $appliance?->msrp ?? 0) }}" min="0" step="0.01"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('msrp')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-receiving-condition" class="block text-sm font-medium text-gray-700 mb-2">Receiving Condition</label>
        <select id="{{ $prefix }}-receiving-condition" name="receiving_condition"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            <option value="">-- Select Condition --</option>
            @foreach($conditions as $condition)
                <option value="{{ $condition }}" {{ old('receiving_condition', $appliance?->receiving_condition) === $condition ? 'selected' : '' }}>
                    {{ $condition }}
                </option>
            @endforeach
        </select>
        @error('receiving_condition')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}-total-parts-cost" class="block text-sm font-medium text-gray-700 mb-2">Total Parts Cost</label>
        <input type="number" id="{{ $prefix }}-total-parts-cost" name="total_parts_cost" value="{{ old('total_parts_cost', $appliance?->total_parts_cost ?? 0) }}" min="0" step="0.01"
               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        @error('total_parts_cost')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
