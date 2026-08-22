@props([
    'paginator',
    'name' => 'limit',
    'options' => [25, 50, 100, 250, 500, 1000],
    'allowAll' => true,
    'pageName' => 'page',
])

@php
    $raw = request()->input($name);
    if ($allowAll && $raw === 'all') {
        $current = 'all';
    } elseif ($raw !== null && $raw !== '' && in_array((int) $raw, $options, true)) {
        $current = (int) $raw;
    } elseif (in_array((int) $paginator->perPage(), $options, true)) {
        $current = (int) $paginator->perPage();
    } else {
        $current = $options[0] ?? 25;
    }

    $query = request()->query();
    unset($query[$name], $query[$pageName]);
    $baseQuery = http_build_query($query);
    $baseUrl = url()->current().($baseQuery !== '' ? '?'.$baseQuery.'&' : '?');
@endphp

<div {{ $attributes->merge(['class' => 'px-6 py-4 border-t border-gray-200 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between']) }}>
    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-700">
        <label for="table-{{ $name }}" class="font-medium whitespace-nowrap">Rows per page:</label>
        <select
            id="table-{{ $name }}"
            name="{{ $name }}"
            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
            onchange="window.location.href = @js($baseUrl) + @js($name) + '=' + encodeURIComponent(this.value) + '&' + @js($pageName) + '=1'"
        >
            @foreach($options as $size)
                <option value="{{ $size }}" @selected((string) $current === (string) $size)>{{ $size }}</option>
            @endforeach
            @if($allowAll)
                <option value="all" @selected($current === 'all')>All</option>
            @endif
        </select>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="text-sm text-gray-600 whitespace-nowrap">
            Showing {{ $paginator->firstItem() ?? 0 }} - {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </div>
        @if($slot->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                {{ $slot }}
            </div>
        @endif
    </div>

    @if($paginator->hasPages())
        <div class="sm:ml-auto">
            {{ $paginator->links() }}
        </div>
    @endif
</div>
