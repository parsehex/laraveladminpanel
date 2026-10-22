<x-admin.filter-bar
    bare
    class="bg-white p-4"
    :action="route($listRoute)"
    :reset-url="route($listRoute)"
    form-class="relative grid grid-cols-1 lg:grid-cols-12 gap-4"
    actions-class="lg:col-span-12 flex flex-wrap justify-end gap-2"
>
    <x-admin.filter-field label="Search" for="search" class="lg:col-span-2">
        <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Model, serial, product, location..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    </x-admin.filter-field>
    <x-admin.filter-field label="Brand" for="brand" class="lg:col-span-2">
        <select id="brand" name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All brands</option>
            @foreach($brands as $brand)
            <option value="{{ $brand }}" @selected(request('brand') === $brand)>{{ $brand }}</option>
            @endforeach
        </select>
    </x-admin.filter-field>
    <x-admin.filter-field label="Location" for="location" class="lg:col-span-2">
        <select id="location" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All locations</option>
            @foreach($locations as $location)
            <option value="{{ $location }}" @selected(request('location') === $location)>{{ $location }}</option>
            @endforeach
        </select>
    </x-admin.filter-field>
    <x-admin.filter-field label="Sub Category" for="sub_category" class="lg:col-span-2">
        <select id="sub_category" name="sub_category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All sub categories</option>
            @foreach($subcategories as $subcategory)
            <option value="{{ $subcategory }}" @selected(request('sub_category') === $subcategory)>{{ $subcategory }}</option>
            @endforeach
        </select>
    </x-admin.filter-field>
    <x-admin.filter-field label="Category" for="category_id" class="lg:col-span-2">
        <select id="category_id" name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All categories</option>
            @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </x-admin.filter-field>
    <x-admin.status-multiselect
        class="lg:col-span-2"
        name="status[]"
        :options="$statuses"
        :selected="$selectedStatuses"
    />
</x-admin.filter-bar>
