@php
    $listViewQuery = collect(request()->query())->except(['view', 'sort', 'direction', 'page'])->all();
    $listViewOptions = [
        \App\Http\Controllers\Admin\InventoryController::LIST_VIEW_MODELS => ['label' => 'By model', 'icon' => 'fa-layer-group'],
        \App\Http\Controllers\Admin\InventoryController::LIST_VIEW_UNITS => ['label' => 'Units', 'icon' => 'fa-list'],
    ];
@endphp
<div class="mr-auto inline-flex overflow-hidden rounded-md border border-gray-300 bg-white shadow-sm" role="group" aria-label="List view">
    @foreach($listViewOptions as $value => $option)
    <a href="{{ route($listRoute, [...$listViewQuery, 'view' => $value]) }}"
       @if($listView === $value) aria-current="page" @endif
       @class([
           'inline-flex items-center px-3 py-1.5 text-sm font-semibold',
           'border-l border-gray-300' => ! $loop->first,
           'bg-blue-600 text-white' => $listView === $value,
           'text-gray-700 hover:bg-gray-50' => $listView !== $value,
       ])>
        <i class="fas {{ $option['icon'] }} mr-2"></i>{{ $option['label'] }}
    </a>
    @endforeach
</div>
