@extends('layouts.admin')

@section('title', 'Categories')
@section('page-title', 'Categories')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">Add category</h2>
        </div>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label for="category-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="category-name" name="name" value="{{ old('name') }}" required maxlength="255"
                       class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-md">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <p class="text-sm text-gray-500">Inactive categories and subcategories stay on existing items and drop out of the pick lists for new ones. Renaming a subcategory updates items in that category that still use the old name.</p>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700">Create category</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Categories</h2>
            <span class="text-sm text-slate-200">{{ $categories->count() }} {{ \Illuminate\Support\Str::plural('category', $categories->count()) }}</span>
        </div>
        @forelse($categories as $category)
            @php
                $categoryHasErrors = $errors->hasBag('subcategory-create-'.$category->id)
                    || $category->subcategories->contains(fn ($subcategory) => $errors->hasBag('subcategory-'.$subcategory->id));
            @endphp
            <div class="border-t border-gray-200" x-data="categorySection(@js($category->id), @js($categoryHasErrors))">
                <button type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 {{ $category->status === 1 ? '' : 'bg-slate-50' }}"
                        @click="toggle()"
                        :aria-expanded="open"
                        aria-controls="category-section-{{ $category->id }}">
                    <i class="fas fa-chevron-right w-4 text-slate-400 transition-transform" :class="open && 'rotate-90'"></i>
                    <span class="font-medium {{ $category->status === 1 ? 'text-gray-900' : 'text-slate-500' }}">{{ $category->name }}</span>
                    @if($category->status !== 1)
                        <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">Inactive</span>
                    @endif
                    <span class="ml-auto text-sm text-gray-500">
                        {{ $category->subcategories->count() }} {{ \Illuminate\Support\Str::plural('subcategory', $category->subcategories->count()) }}
                        <span class="px-1 text-gray-300">·</span>
                        {{ $category->models_count }} {{ \Illuminate\Support\Str::plural('model', $category->models_count) }}
                        <span class="px-1 text-gray-300">·</span>
                        {{ $category->appliances_count }} {{ \Illuminate\Support\Str::plural('item', $category->appliances_count) }}
                    </span>
                </button>
                <div id="category-section-{{ $category->id }}" x-show="open" x-cloak class="border-t border-gray-100 bg-slate-50 px-4 py-5 sm:px-6">
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1">
                            <label for="category-name-{{ $category->id }}" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" id="category-name-{{ $category->id }}" name="name" value="{{ $category->name }}" required maxlength="255"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
                        </div>
                        <div class="w-full lg:w-40">
                            <label for="category-status-{{ $category->id }}" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="category-status-{{ $category->id }}" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
                                <option value="1" @selected($category->status === 1)>Active</option>
                                <option value="0" @selected($category->status !== 1)>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="inline-flex h-[42px] items-center justify-center gap-2 rounded-md bg-slate-700 px-4 text-white hover:bg-slate-800" aria-label="Save {{ $category->name }}">
                            <i class="fas fa-save"></i>
                            <span>Save</span>
                        </button>
                    </form>

                    <div class="mt-6 overflow-hidden rounded-md border border-gray-200 bg-white">
                        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-end">
                            <form method="POST" action="{{ route('admin.categories.subcategories.store', $category) }}" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                                @csrf
                                <div class="flex-1">
                                    <label for="subcategory-name-{{ $category->id }}" class="block text-sm font-medium text-gray-700 mb-1">Add subcategory</label>
                                    <input type="text" id="subcategory-name-{{ $category->id }}" name="subcategory_name" maxlength="255"
                                           value="{{ $errors->hasBag('subcategory-create-'.$category->id) ? old('subcategory_name') : '' }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    @error('subcategory_name', 'subcategory-create-'.$category->id)
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="px-4 py-2 rounded-md bg-blue-600 text-white font-semibold hover:bg-blue-700">Add</button>
                            </form>
                        </div>
                        @if($category->subcategories->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Items</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($category->subcategories as $subcategory)
                                    <tr class="{{ $subcategory->status === 1 ? '' : 'bg-slate-50 text-slate-500' }}">
                                        <td class="px-4 py-3">
                                            <form id="subcategory-form-{{ $subcategory->id }}" method="POST" action="{{ route('admin.categories.subcategories.update', [$category, $subcategory]) }}">
                                                @csrf
                                                @method('PATCH')
                                            </form>
                                            <input form="subcategory-form-{{ $subcategory->id }}" type="text" name="subcategory_name" required maxlength="255"
                                                   value="{{ $errors->hasBag('subcategory-'.$subcategory->id) ? old('subcategory_name') : $subcategory->name }}"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                            @error('subcategory_name', 'subcategory-'.$subcategory->id)
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-3">
                                            <select form="subcategory-form-{{ $subcategory->id }}" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                                <option value="1" @selected($subcategory->status === 1)>Active</option>
                                                <option value="0" @selected($subcategory->status !== 1)>Inactive</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-700">{{ $subcategory->item_count }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <button form="subcategory-form-{{ $subcategory->id }}" type="submit" class="inline-flex h-[42px] w-[42px] items-center justify-center rounded-md bg-slate-700 text-white hover:bg-slate-800" aria-label="Save {{ $subcategory->name }}" title="Save">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="px-4 py-6 text-sm text-gray-500">No subcategories yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="px-6 py-8 text-center text-gray-500">No categories found.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.categorySection = function (id, forceOpen) {
        return {
            open: false,
            init() {
                this.open = forceOpen || localStorage.getItem('categorySection.' + id) === '1';
            },
            toggle() {
                this.open = !this.open;
                localStorage.setItem('categorySection.' + id, this.open ? '1' : '0');
            },
        };
    };
</script>
@endpush
