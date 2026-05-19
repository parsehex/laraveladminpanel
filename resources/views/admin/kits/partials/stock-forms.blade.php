<div class="border-t border-gray-200 pt-5">
    <h3 class="text-base font-semibold text-gray-900">Adjust Stock</h3>
    <form method="POST" action="{{ route('admin.kits.inventory.adjust-stock') }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
        @csrf
        <select name="part_name" class="px-3 py-2 border border-gray-300 rounded-md" required>
            <option value="">Select {{ $platformRequired ? 'Kit' : 'Part' }}</option>
            @foreach($items as $item)
                <option value="{{ $item->part_name }}">{{ $item->label }}</option>
            @endforeach
        </select>
        @if($platformRequired)
            <select name="platform" class="px-3 py-2 border border-gray-300 rounded-md" required>
                <option value="">Platform</option>
                <option value="amazon">Amazon</option>
                <option value="shopify">Shopify</option>
            </select>
        @endif
        <input type="number" name="adjustment" class="px-3 py-2 border border-gray-300 rounded-md {{ $platformRequired ? '' : 'md:col-span-2' }}" placeholder="Adjustment (+/-)" required>
        <button class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold">Adjust</button>
    </form>
</div>

@if($platformRequired)
<div class="border-t border-gray-200 pt-5">
    <h3 class="text-base font-semibold text-gray-900">Adjust Min Levels</h3>
    <form method="POST" action="{{ route('admin.kits.inventory.adjust-min-level') }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
        @csrf
        <select name="part_name" class="px-3 py-2 border border-gray-300 rounded-md" required>
            <option value="">Select Kit</option>
            @foreach($items as $item)
                <option value="{{ $item->part_name }}">{{ $item->label }}</option>
            @endforeach
        </select>
        <select name="platform" class="px-3 py-2 border border-gray-300 rounded-md" required>
            <option value="">Platform</option>
            <option value="amazon">Amazon</option>
            <option value="shopify">Shopify</option>
        </select>
        <input type="number" min="0" name="new_min_level" class="px-3 py-2 border border-gray-300 rounded-md" placeholder="New Min Level" required>
        <button class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold">Adjust Min Level</button>
    </form>
</div>
@endif
