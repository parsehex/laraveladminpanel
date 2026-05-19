@php
    $truck = $truck ?? null;
@endphp

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <div>
        <x-form.input
            name="name"
            label="Name"
            type="text"
            :value="old('name', $truck?->name)"
            required="true"
        />
    </div>

    <div>
        <x-form.input
            name="units_on_truck"
            label="Units on truck"
            type="number"
            :value="old('units_on_truck', $truck?->units_on_truck)"
            required="true"
        />
    </div>

    <div>
        <x-form.input
            name="cost_of_truck"
            label="Cost of truck"
            type="number"
            step="0.01"
            min="0"
            :value="old('cost_of_truck', $truck?->cost_of_truck)"
            required="true"
        />
    </div>

    <div>
        <x-form.input
            name="arrival_date"
            label="Arrival date"
            type="date"
            :value="old('arrival_date', $truck?->arrival_date)"
            required="true"
        />
    </div>

    <div>
        <x-form.select
            name="status"
            label="Status"
            :options="['active' => 'Active', 'inactive' => 'Inactive']"
            :value="old('status', $truck?->status ?? 'active')"
            required="true"
        />
    </div>

    <div class="md:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
        <textarea id="notes" name="notes" rows="4"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">{{ old('notes', $truck?->notes) }}</textarea>
        @error('notes')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
