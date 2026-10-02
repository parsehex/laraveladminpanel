@props(['action', 'range'])

@php
    $otherQuery = request()->except([...\App\Support\InventoryCostRange::QUERY_KEYS, 'page']);
    $activePeriod = $range->period ?? 'all';
@endphp

<div class="border-b border-blue-100 bg-blue-50 p-3 space-y-2">
    <div class="flex flex-wrap items-center gap-2">
        @foreach(\App\Support\InventoryCostRange::PRESETS as $periodKey => $periodLabel)
            <a href="{{ $range->presetUrl($action, request()->query(), $periodKey) }}"
               class="px-3 py-1.5 rounded-md text-sm font-semibold border {{ $activePeriod === $periodKey ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                {{ $periodLabel }}
            </a>
        @endforeach

        <form method="GET" action="{{ $action }}" class="flex flex-wrap items-center gap-2">
            @foreach($otherQuery as $key => $value)
                @if(is_array($value))
                    @foreach($value as $nestedValue)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $nestedValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label class="text-sm font-semibold text-gray-700">
                Start:
                <input type="date" name="cost_from" value="{{ $range->from?->toDateString() }}" class="ml-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-gray-700">
                End (EOD):
                <input type="date" name="cost_date" value="{{ $range->to?->toDateString() }}" class="ml-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm font-normal">
            </label>
            <button type="submit" class="rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 border border-gray-300 hover:bg-gray-100">Apply</button>
        </form>
    </div>

    <p class="text-sm text-blue-700">
        <span class="font-semibold">Showing {{ $range->label() }}.</span>
        @if($range->from || $range->to)
            Units added in this range, with status as of {{ $range->usesStatusHistory() ? 'end of day '.$range->to->format('m/d/Y') : 'now' }}.
        @endif
    </p>
</div>
