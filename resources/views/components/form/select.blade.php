@props(['name', 'label', 'options' => [], 'required' => false, 'value' => ''])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    <select id="{{ $name }}" 
            name="{{ $name }}" 
            {{ $attributes->merge(['class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500']) }}
            @if($required) required @endif>
        
        <option value="">Select {{ $label }}</option>
        @foreach($options as $key => $option)
            <option value="{{ $key }}" {{ old($name, $value) == $key ? 'selected' : '' }}>
                {{ $option }}
            </option>
        @endforeach
    </select>
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>