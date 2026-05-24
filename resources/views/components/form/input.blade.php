@props(['name', 'label', 'type' => 'text', 'required' => false, 'value' => ''])

<div class="mb-4">
    <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    @if($type === 'password')
        <div class="relative">
            <input type="password"
                   id="{{ $name }}"
                   name="{{ $name }}"
                   value="{{ old($name, $value) }}"
                   {{ $attributes->merge(['class' => 'w-full px-4 py-2.5 pr-12 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500']) }}
                   @if($required) required @endif>
            <button type="button"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-700"
                    data-toggle-password="{{ $name }}"
                    aria-label="Show password">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    @else
        <input type="{{ $type }}"
               id="{{ $name }}"
               name="{{ $name }}"
               value="{{ old($name, $value) }}"
               {{ $attributes->merge(['class' => 'w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500']) }}
               @if($required) required @endif>
    @endif
    
    @error($name)
        <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
@push('scripts')
<script>
    $(document).on('click', '[data-toggle-password]', function () {
        const input = document.getElementById($(this).data('toggle-password'));
        const icon = $(this).find('i');

        if (! input) {
            return;
        }

        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        icon.toggleClass('fa-eye', showing).toggleClass('fa-eye-slash', !showing);
        $(this).attr('aria-label', showing ? 'Show password' : 'Hide password');
    });
</script>
@endpush
@endonce
