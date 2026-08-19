@props([
    'title' => null,
    'table' => null,
    'columnStorageKey' => null,
    'columns' => [],
    'bare' => false,
])

@php
    if ($table instanceof \App\Support\DataTable) {
        $columnStorageKey = $table->storageKey();
        $sourceColumns = $table->columnsForView();
    } else {
        $sourceColumns = $columns;
    }

    $toggleableColumns = collect($sourceColumns)
        ->map(function ($column) {
            if (is_array($column)) {
                return [
                    'key' => $column['key'],
                    'label' => $column['label'] ?? $column['key'],
                    'default' => $column['default'] ?? true,
                ];
            }

            return ['key' => $column, 'label' => $column, 'default' => true];
        })
        ->values()
        ->all();
@endphp

<div @if($bare) {{ $attributes }} @else {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow overflow-hidden']) }} @endif>
    @if($title)
        <div class="bg-blue-600 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-white">{{ $title }}</h2>
            <div class="flex flex-wrap items-center gap-2">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>
        </div>
    @endif

    @isset($filters)
        {{ $filters }}
    @endisset

    <div x-data="adminDataTable(@js($columnStorageKey), @js($toggleableColumns))" x-init="init()">
        @if((! $title && isset($header)) || ($columnStorageKey && count($toggleableColumns)))
            <div class="flex flex-wrap items-center justify-end gap-2 border-b border-gray-200 bg-gray-50 px-4 py-2">
                @if(! $title)
                    @isset($header)
                        {{ $header }}
                    @endisset
                @endif
                @if($columnStorageKey && count($toggleableColumns))
                <div class="relative" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        <i class="fas fa-columns text-gray-500"></i>
                        Columns
                        <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                    </button>
                    <div x-cloak
                         x-show="open"
                         @click.outside="open = false"
                         class="absolute right-0 z-50 mt-2 w-56 rounded-lg border border-gray-200 bg-white p-2 shadow-lg">
                        @foreach($toggleableColumns as $column)
                            <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-gray-700 hover:bg-blue-50">
                                <input type="checkbox"
                                       x-model="visible['{{ $column['key'] }}']"
                                       class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>{{ $column['label'] }}</span>
                            </label>
                        @endforeach
                        <div class="mt-1 space-y-0.5 border-t border-gray-100 pt-1">
                            <button type="button"
                                    @click="showAllColumns()"
                                    class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                                Show all
                            </button>
                            <button type="button"
                                    @click="hideAllColumns()"
                                    class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                                Hide all
                            </button>
                            <button type="button"
                                    @click="resetColumns()"
                                    class="w-full rounded-md px-2 py-1.5 text-left text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                                Reset to defaults
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        @endif

        <div class="wide-table-shell" data-wide-table>
            <div class="wide-table-top-scroll" data-wide-table-top-scroll><div></div></div>
            <div class="wide-table-scroll" data-wide-table-scroll>
                {{ $slot }}
            </div>
        </div>
    </div>

    @isset($footer)
        {{ $footer }}
    @endisset
</div>
