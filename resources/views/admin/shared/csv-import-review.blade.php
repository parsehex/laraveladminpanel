@extends('layouts.admin')

@section('title', $title)
@section('page-title', $title)
@section('page-subtitle', $subtitle ?? '')

@section('page-actions')
    <a href="{{ $backRoute }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
        {{ $backLabel ?? 'Back' }}
    </a>
@endsection

@section('content')
<div class="flex flex-col gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 font-semibold text-green-800">
                {{ $preview->createCount() }} new
            </span>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 font-semibold text-blue-800">
                {{ $preview->updateCount() }} updates
            </span>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-700">
                {{ $preview->unchangedCount }} unchanged
            </span>
            @if($preview->sideEffectRowCount() > 0)
                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 font-semibold text-amber-900">
                    {{ $preview->sideEffectRowCount() }} side effects
                </span>
            @endif
            <span class="inline-flex items-center rounded-full {{ $preview->errorCount() > 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700' }} px-3 py-1 font-semibold">
                {{ $preview->errorCount() }} errors
            </span>
        </div>
        @if(! empty($matchHelp))
            <p class="mt-3 text-sm text-gray-600">{{ $matchHelp }}</p>
        @endif
        <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="{{ $cancelRoute }}">
                @csrf
                <button type="submit" class="rounded-md bg-gray-500 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-600">
                    Cancel
                </button>
            </form>
            @if($preview->canConfirm())
                <form id="appliance-import-confirm" method="POST" action="{{ $confirmRoute }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        <i class="fas fa-check mr-1"></i>Confirm import
                    </button>
                </form>
            @else
                <button type="button" disabled class="cursor-not-allowed rounded-md bg-blue-300 px-4 py-2 text-sm font-semibold text-white">
                    <i class="fas fa-check mr-1"></i>Confirm import
                </button>
            @endif
        </div>
    </div>

    @if($preview->errors !== [])
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-red-700">Will skip / errors</h2>
                <p class="text-sm text-gray-500">Fix these rows in the CSV and upload again.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Row</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Match key</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Errors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($preview->errors as $error)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $error['row_number'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $error['match_key'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-red-700">
                                    <ul class="list-disc pl-4">
                                        @foreach($error['errors'] as $message)
                                            <li>{{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($preview->creates !== [])
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Will create</h2>
                <p class="text-sm text-gray-500">{{ $preview->createCount() }} new row(s).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach($preview->createColumns as $label)
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($preview->creates as $row)
                            <tr>
                                @foreach($preview->createColumns as $field => $label)
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        @php($value = $row[$field] ?? null)
                                        @if(is_numeric($value) && in_array($field, ['cost_of_truck', 'shipping_cost', 'retail_price', 'your_price', 'msrp', 'price', 'sold_price'], true))
                                            ${{ number_format((float) $value, 2) }}
                                        @else
                                            {{ $value === null || $value === '' ? '—' : $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($preview->updates !== [])
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Will update</h2>
                <p class="text-sm text-gray-500">{{ $preview->updateCount() }} existing row(s) with field changes.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Matched As</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Changes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($preview->updates as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $row['match_key'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <ul class="space-y-1">
                                        @foreach($row['changes'] as $change)
                                            <li>
                                                <span class="font-medium text-gray-800">{{ $change['label'] }}:</span>
                                                <span class="text-gray-500">{{ $change['from'] }}</span>
                                                <span class="text-gray-400">→</span>
                                                <span class="rounded bg-amber-50 px-1 font-semibold text-amber-900">{{ $change['to'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($preview->sideEffectRows !== [])
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Will apply side effects</h2>
                <p class="text-sm text-gray-500">{{ $preview->sideEffectRowCount() }} matched row(s) with no field changes, but related records will still be updated.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Row</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Matched As</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Effects</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($preview->sideEffectRows as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $row['row_number'] }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $row['match_key'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <ul class="list-disc pl-4">
                                        @foreach($row['effects'] as $effect)
                                            <li>{{ $effect }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($preview->hasSideEffects() && $preview->sideEffects !== [])
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Catalog side effects</h2>
            </div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                @foreach($preview->sideEffects as $sectionTitle => $items)
                    @if($items !== [])
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">{{ $sectionTitle }}</h3>
                            @if(($chooseCategoryTypes ?? false) && $sectionTitle === 'Categories')
                                <p class="mt-1 text-sm text-gray-500">Choose a type for each new category.</p>
                                @error('new_categories')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <ul class="mt-3 space-y-3">
                                    @foreach($items as $index => $item)
                                        <li class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <span class="text-sm text-gray-700">{{ $item }}</span>
                                            <input type="hidden" name="new_categories[{{ $index }}][name]" value="{{ $item }}" form="appliance-import-confirm">
                                            <select id="new-category-type-{{ $index }}" name="new_categories[{{ $index }}][type]" form="appliance-import-confirm"
                                                    aria-label="Type for {{ $item }}"
                                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm sm:w-40">
                                                @foreach(\App\Enums\ItemType::cases() as $type)
                                                    <option value="{{ $type->value }}" @selected(old('new_categories.'.$index.'.type', 'appliance') === $type->value)>{{ $type->label() }}</option>
                                                @endforeach
                                            </select>
                                            @error('new_categories.'.$index.'.type')
                                                <p class="text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <ul class="mt-2 list-disc pl-4 text-sm text-gray-700">
                                    @foreach($items as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if($preview->creates === [] && $preview->updates === [] && $preview->errors === [] && $preview->sideEffectRows === [])
        <div class="bg-white rounded-lg shadow p-6 text-sm text-gray-600">
            No create or update changes found in this CSV.
            @if($preview->unchangedCount > 0)
                {{ $preview->unchangedCount }} row(s) matched with no field changes.
            @endif
        </div>
    @endif
</div>
@endsection
