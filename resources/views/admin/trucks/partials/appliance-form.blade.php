@php
    $appliance = $appliance ?? null;
    $prefix = $prefix ?? 'appliance';
    $conditions = \App\Models\TruckAppliance::RECEIVING_CONDITIONS;
    $selectedCategory = old('category', $appliance?->category?->name);
    $selectedSubcategory = old('subcategory', $appliance?->subcategory);
    $selectedModel = old('model_number', $appliance?->model?->model_number);
    $isReturnsTruck = stripos($truck->name, 'Returns') !== false;
@endphp

<input type="hidden" name="truck_id" value="{{ $truck->id }}">

<div class="space-y-6 legacy-appliance-form" data-appliance-form>
    <div>
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Basic Info</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="{{ $prefix }}-category" class="block text-sm font-semibold text-gray-700 mb-2">Category: <span class="text-red-500">*</span></label>
                <div class="flex gap-2" data-quick-create-wrapper>
                    <select id="{{ $prefix }}-category" name="category" required data-legacy-category data-ajax-dropdown="category" data-value-field="name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Search category...</option>
                        @if($selectedCategory)
                            <option value="{{ $selectedCategory }}" selected>{{ $selectedCategory }}</option>
                        @endif
                    </select>
                    @if(auth()->user()?->can('category.create'))
                        <button type="button" class="inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white hover:bg-blue-700" data-open-quick-create="category" data-target="#{{ $prefix }}-category">
                            <i class="fas fa-plus"></i>
                        </button>
                    @endif
                </div>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="{{ $selectedCategory ? '' : 'hidden' }}" data-subcategory-container>
                <label for="{{ $prefix }}-subcategory" class="block text-sm font-semibold text-gray-700 mb-2">Sub-Category:</label>
                <div class="flex gap-2" data-quick-create-wrapper>
                    <select id="{{ $prefix }}-subcategory" name="subcategory" data-ajax-dropdown="subcategory" data-category-source="#{{ $prefix }}-category"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Search subcategory...</option>
                        @if($selectedSubcategory)
                            <option value="{{ $selectedSubcategory }}" selected>{{ $selectedSubcategory }}</option>
                        @endif
                    </select>
                    @if(auth()->user()?->can('category.create'))
                        <button type="button" class="inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white hover:bg-blue-700" data-open-quick-create="subcategory" data-target="#{{ $prefix }}-subcategory">
                            <i class="fas fa-plus"></i>
                        </button>
                    @endif
                </div>
                @error('subcategory')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefix }}-model-number" class="block text-sm font-semibold text-gray-700 mb-2">Model #: <span class="text-red-500">*</span></label>
                <div class="flex gap-2" data-quick-create-wrapper>
                    <select id="{{ $prefix }}-model-number" name="model_number" required data-ajax-dropdown="model" data-legacy-model-select data-category-source="#{{ $prefix }}-category"
                            class="w-full caps px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Search model...</option>
                        @if($selectedModel)
                            <option value="{{ $selectedModel }}" selected>{{ $selectedModel }}</option>
                        @endif
                    </select>
                    @canAccess('models.create')
                        <button type="button" class="inline-flex h-[42px] w-[42px] flex-shrink-0 items-center justify-center rounded-md bg-blue-600 text-white hover:bg-blue-700" data-open-quick-create="model" data-target="#{{ $prefix }}-model-number">
                            <i class="fas fa-plus"></i>
                        </button>
                    @endcanAccess
                </div>
                @error('model_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefix }}-serial-number" class="block text-sm font-semibold text-gray-700 mb-2">Serial #: <span class="text-red-500">*</span></label>
                <input type="text" id="{{ $prefix }}-serial-number" name="serial_number" value="{{ old('serial_number', $appliance?->serial_number) }}" placeholder="Serial #" required pattern="[A-Z0-9-]+"
                       class="caps w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                @error('serial_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Triage Info</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="{{ $prefix }}-brand" class="block text-sm font-semibold text-gray-700 mb-2">Brand: <span class="text-red-500">*</span></label>
                <input type="text" id="{{ $prefix }}-brand" name="brand" value="{{ old('brand', $appliance?->brand) }}" placeholder="Brand" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                @error('brand')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefix }}-product-name" class="block text-sm font-semibold text-gray-700 mb-2">Product Name:</label>
                <input type="text" id="{{ $prefix }}-product-name" name="product_name" value="{{ old('product_name', $appliance?->product_name) }}" placeholder="Product Name"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                @error('product_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefix }}-msrp" class="block text-sm font-semibold text-gray-700 mb-2">MSRP:</label>
                <input type="number" id="{{ $prefix }}-msrp" name="msrp" value="{{ old('msrp', $appliance?->msrp ?? 0) }}" placeholder="MSRP" min="0" step="0.01"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                @error('msrp')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefix }}-receiving-condition" class="block text-sm font-semibold text-gray-700 mb-2">Receiving Condition: <span class="text-red-500">*</span></label>
                <select id="{{ $prefix }}-receiving-condition" name="receiving_condition" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">--Choose--</option>
                    @foreach($conditions as $condition)
                        <option value="{{ $condition }}" @selected(old('receiving_condition', $appliance?->receiving_condition) === $condition)>{{ $condition }}</option>
                    @endforeach
                </select>
                @error('receiving_condition')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="{{ in_array($selectedCategory, ['Ranges', 'Dryers'], true) ? '' : 'hidden' }}" data-fuel-type-container>
                <label for="{{ $prefix }}-fuel-type" class="block text-sm font-semibold text-gray-700 mb-2">Fuel Type:</label>
                <select id="{{ $prefix }}-fuel-type" name="fuel_type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    @foreach(['N/A', 'NG', 'LP'] as $fuelType)
                        <option value="{{ $fuelType }}" @selected(old('fuel_type', $appliance?->fuel_type ?? 'N/A') === $fuelType)>{{ $fuelType }}</option>
                    @endforeach
                </select>
                @error('fuel_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefix }}-total-parts-cost" class="block text-sm font-semibold text-gray-700 mb-2">Total Parts Cost:</label>
                <input type="number" id="{{ $prefix }}-total-parts-cost" name="total_parts_cost" value="{{ old('total_parts_cost', $appliance?->total_parts_cost ?? 0) }}" placeholder="Parts Cost" min="0" step="0.01"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                @error('total_parts_cost')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    @if($isReturnsTruck)
    <div>
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Return Info</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="{{ $prefix }}-original-order-number" class="block text-sm font-semibold text-gray-700 mb-2">Original Order #:</label>
                <input type="text" id="{{ $prefix }}-original-order-number" name="original_order_number" value="{{ old('original_order_number', $appliance?->original_order_number) }}" placeholder="Original Order #"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="{{ $prefix }}-return-reason" class="block text-sm font-semibold text-gray-700 mb-2">Return Reason:</label>
                <input type="text" id="{{ $prefix }}-return-reason" name="return_reason" value="{{ old('return_reason', $appliance?->return_reason) }}" list="{{ $prefix }}-return-reason-options" placeholder="Select or type a reason"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <datalist id="{{ $prefix }}-return-reason-options">
                    <option value="Defective">
                    <option value="Wrong Item">
                    <option value="Customer Changed Mind">
                    <option value="Damaged in Shipping">
                    <option value="Other">
                </datalist>
            </div>
            <div>
                <label for="{{ $prefix }}-return-problems" class="block text-sm font-semibold text-gray-700 mb-2">Problems:</label>
                <textarea id="{{ $prefix }}-return-problems" name="return_problems" rows="2" placeholder="Describe any problems"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">{{ old('return_problems', $appliance?->return_problems) }}</textarea>
            </div>
        </div>
    </div>
    @endif
</div>
