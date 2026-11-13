@extends('layouts.admin')

@section('title', 'Check in')
@section('page-title', 'Check in')
@section('page-subtitle', 'Confirm the item')

@section('content')
<div class="mx-auto max-w-lg space-y-4">
    @if(count($checkIn->photos ?? []) > 0)
    <div class="flex gap-2 overflow-x-auto">
        @foreach($checkIn->photos as $path)
        <img src="{{ route('admin.inventory.check-in.photo', ['checkIn' => $checkIn, 'photo' => $path]) }}" alt="Label photo" class="h-28 w-28 flex-shrink-0 rounded-md border border-gray-200 object-cover bg-gray-100">
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('admin.inventory.check-in.confirm', $checkIn) }}" class="space-y-4">
        @csrf

        <section class="rounded-lg bg-white p-4 shadow space-y-3">
            <h2 class="text-sm font-semibold text-gray-900">From the label</h2>
            <p class="text-xs text-gray-500">Correct anything that was misread, then update the matches before you confirm.</p>
            <div>
                <label for="brand" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Brand</label>
                <input id="brand" name="brand" value="{{ old('brand', $extraction['brand'] ?? '') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="model_number" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Model</label>
                <input id="model_number" name="model_number" value="{{ old('model_number', $extraction['model_number'] ?? '') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="product_name" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Product</label>
                <input id="product_name" name="product_name" value="{{ old('product_name', $extraction['product_name'] ?? '') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="serial_number" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Serial or SSCC</label>
                <input id="serial_number" name="serial_number" value="{{ old('serial_number', $extraction['serial_number'] ?? '') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" name="intent" value="lookup" class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                Update matches
            </button>
        </section>

        <section class="rounded-lg bg-white p-4 shadow space-y-3">
            <h2 class="text-sm font-semibold text-gray-900">
                @if($match['kind'] === 'serial')
                    Matching serial
                @elseif($match['kind'] === 'model')
                    Open units of this model
                @else
                    No open unit matched
                @endif
            </h2>
            @if($match['truncated'])
            <p class="text-xs text-gray-500">Showing the first 25.</p>
            @endif
            @if($match['units']->isEmpty())
            <p class="text-sm text-gray-600">
                Nothing already in inventory matched. Add the item to a truck below.
                @if($match['known_model'])
                    This model is already in the catalog, so the new unit will use it.
                @endif
            </p>
            @else
            <div class="space-y-2">
                @foreach($match['units'] as $unit)
                <label class="flex items-start gap-3 rounded-md border border-gray-200 px-3 py-2">
                    <input type="radio" name="appliance_id" value="{{ $unit->id }}" class="mt-1" @checked((string) old('appliance_id') === (string) $unit->id)>
                    <span class="min-w-0 text-sm">
                        <span class="block font-semibold text-gray-900">{{ $unit->unit_label ?: '#'.$unit->id }}</span>
                        <span class="block text-gray-600">{{ $unit->truck?->name ?: 'No truck' }} · {{ $unit->model?->model_number ?: 'No model' }}</span>
                        <span class="block text-gray-500">{{ $unit->status ?: 'No status' }}@if($unit->serial_number) · {{ $unit->serial_number }}@endif</span>
                        @if($unit->checked_in_at)
                        <span class="mt-1 inline-flex rounded bg-amber-100 px-1.5 py-0.5 text-xs font-semibold text-amber-800">Already checked in</span>
                        @endif
                    </span>
                </label>
                @endforeach
            </div>
            @if($canEdit)
            <button type="submit" name="intent" value="match" class="inline-flex w-full items-center justify-center rounded-md bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Check in this unit
            </button>
            @endif
            @endif
        </section>

        @if($canCreate)
        <section class="rounded-lg bg-white p-4 shadow space-y-3">
            <h2 class="text-sm font-semibold text-gray-900">Or add it</h2>
            <p class="text-xs text-gray-500">Use this when the box is not one of the units above. A new category is saved as furniture.</p>
            <div>
                <label for="truck_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Truck</label>
                <select id="truck_id" name="truck_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Choose a truck</option>
                    @foreach($trucks as $truck)
                    <option value="{{ $truck->id }}" @selected((string) old('truck_id') === (string) $truck->id)>{{ $truck->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Category</label>
                <input id="category" name="category" value="{{ old('category') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="msrp" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">MSRP</label>
                <input id="msrp" name="msrp" inputmode="decimal" value="{{ old('msrp', '0') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" name="intent" value="add" class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                Add to this truck
            </button>
        </section>
        @endif
    </form>
</div>
@endsection
