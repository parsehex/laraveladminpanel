@props(['name', 'label', 'type' => 'text', 'required' => false, 'value' => ''])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    <input type="{{ $type }}" 
           id="{{ $name }}" 
           name="{{ $name }}" 
           value="{{ old($name, $value) }}"
           {{ $attributes->merge(['class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500']) }}
           @if($required) required @endif>
    
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>