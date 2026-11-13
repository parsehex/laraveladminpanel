@extends('layouts.admin')

@section('title', 'Check in')
@section('page-title', 'Check in')
@section('page-subtitle', 'One item')

@section('content')
<div class="mx-auto max-w-lg">
    <form method="POST" action="{{ route('admin.inventory.check-in.store') }}" enctype="multipart/form-data" class="rounded-lg bg-white p-4 shadow space-y-4">
        @csrf
        <p class="text-sm text-gray-600">Take one or more photos of this item’s label. The truck is chosen only if the item is not already in the system.</p>
        <div>
            <label for="photos" class="block text-sm font-semibold text-gray-800">Photos</label>
            <input id="photos" type="file" name="photos[]" accept="image/*" multiple required class="mt-2 block w-full text-sm text-gray-700">
            <p class="mt-1 text-xs text-gray-500">Up to 5 photos, 8MB each.</p>
        </div>
        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
            Read label
        </button>
    </form>
</div>
@endsection
