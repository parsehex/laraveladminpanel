@extends('layouts.admin')

@section('title', 'Model Parts: '.$model->model_number)
@section('page-title', 'Model Parts')
@section('page-subtitle', $model->model_number)

@php
    $placeholderSvg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjEwMCIgeT0iNzUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjOTk5Ij5ObyBJbWFnZTwvdGV4dD48L3N2Zz4=';
    $firstDiagramKey = ! empty($diagrams) ? (array_values($diagrams)[0]['key'] ?? null) : null;
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="text-sm text-gray-500">
            <a href="{{ route('admin.models.index') }}" class="text-blue-600 hover:text-blue-800">Models</a>
            <span class="mx-2">/</span>
            <span class="text-gray-800">{{ $model->model_number }}</span>
        </nav>
        <a href="{{ route('admin.models.index') }}" class="inline-flex items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Models
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-white">{{ $model->model_number }}</h2>
                <p class="text-sm text-blue-100 mt-1">
                    {{ $model->product_name ?: 'No product name' }}
                    · {{ $model->brand ?: 'No brand' }}
                    · {{ $model->category?->name ?? 'Uncategorized' }}
                    · MSRP ${{ number_format((float) $model->msrp, 2) }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label for="variation_filter" class="text-sm font-medium text-white">Variation</label>
                <select id="variation_filter" class="rounded-md border-0 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    @foreach($variations as $var)
                        <option value="{{ $var }}" @selected($variation === $var)>{{ $var }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="p-6 space-y-6">
            @if($variationFallback)
                <div class="rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                    Invalid variation selected. Defaulting to: {{ $variation }}
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search-keyword" class="block text-sm font-medium text-gray-700 mb-1">Search parts</label>
                    <input type="text" id="search-keyword" value="{{ $search }}" placeholder="Search by part #, name, or diagram..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="button" id="filter-parts-btn" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Search
                </button>
            </div>

            @if($parts->isEmpty())
                <div class="rounded-md border border-blue-200 bg-blue-50 px-4 py-6 text-center text-blue-800">
                    No parts found for variation "{{ $variation }}".
                    @if($search !== '')
                        Try clearing the search, or
                    @endif
                    import CSV data from the Models page.
                </div>
            @else
                <p class="text-sm font-semibold text-blue-600">({{ $parts->count() }} parts)</p>

                @if(empty($diagrams))
                    <div class="rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        Parts loaded but no diagrams grouped.
                    </div>
                @else
                    <div class="diagram-grid">
                        @foreach($diagrams as $diagram)
                            @php
                                $proxyUrl = $diagram['image_url']
                                    ? route('admin.models.proxy-image', ['url' => $diagram['image_url']])
                                    : $placeholderSvg;
                            @endphp
                            <button type="button"
                                    class="diagram-card {{ $loop->first ? 'active-diagram' : '' }}"
                                    data-diag-key="{{ $diagram['key'] }}">
                                <img src="{{ $proxyUrl }}"
                                     alt="{{ $diagram['name'] }}"
                                     class="diagram-image"
                                     onerror="this.src='{{ $placeholderSvg }}'">
                                <div class="diagram-label">{{ $diagram['name'] }} ({{ count($diagram['parts']) }} parts)</div>
                            </button>
                        @endforeach
                    </div>

                    @foreach($diagrams as $diagram)
                        @php
                            $proxyUrl = $diagram['image_url']
                                ? route('admin.models.proxy-image', ['url' => $diagram['image_url']])
                                : null;
                        @endphp
                        <div id="detail-{{ $diagram['key'] }}" class="parts-detail {{ $loop->first ? 'show' : '' }}">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $diagram['name'] }}</h3>
                            <div class="diagram-detail-row">
                                <div class="diagram-image-col">
                                    @if($proxyUrl)
                                        <button type="button" class="block w-full text-left" data-open-zoom="{{ $proxyUrl }}">
                                            <img src="{{ $proxyUrl }}"
                                                 alt="Full diagram — click to zoom"
                                                 class="full-diagram-img"
                                                 onerror="this.style.display='none'">
                                        </button>
                                    @endif
                                </div>
                                <div class="parts-table-col">
                                    <div class="overflow-x-auto max-h-[600px] rounded-lg border border-gray-200 bg-white">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50 sticky top-0">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Item #</th>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Part #</th>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Description</th>
                                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500">Stock</th>
                                                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach($diagram['parts'] as $part)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700">{{ $part->item ?: 'N/A' }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-semibold text-gray-900">{{ $part->part_number }}</td>
                                                        <td class="px-3 py-2 text-sm text-gray-700" title="{{ $part->product_name }}">
                                                            {{ \Illuminate\Support\Str::limit($part->product_name ?: 'N/A', 50) }}
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-right text-sm text-gray-700">{{ number_format((int) $part->total_stock) }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-right text-sm">
                                                            @canAccess('parts.view')
                                                                <a href="{{ route('admin.parts.index', ['search' => $part->part_number, 'part_id' => $part->id]) }}"
                                                                   class="font-semibold text-blue-600 hover:text-blue-800">View</a>
                                                            @endcanAccess
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            @endif
        </div>
    </div>
</div>

{{-- Zoom modal --}}
<div id="image-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4" aria-hidden="true">
    <div class="relative max-h-[90vh] max-w-[90vw] overflow-auto rounded-lg bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h3 class="text-base font-semibold text-gray-900">Diagram Zoom</h3>
            <button type="button" id="close-image-modal" class="rounded-md px-2 py-1 text-gray-500 hover:bg-gray-100 hover:text-gray-800" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-2 text-center">
            <img id="modal-image" src="" alt="Zoomed diagram" class="mx-auto max-h-[80vh] max-w-full">
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .diagram-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.25rem;
    }
    .diagram-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        background: #fff;
        text-align: left;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .diagram-card:hover {
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
        transform: translateY(-2px);
    }
    .diagram-card.active-diagram {
        box-shadow: 0 0 0 3px #3b82f6;
    }
    .diagram-image {
        width: 100%;
        height: 150px;
        object-fit: cover;
        display: block;
        background: #f3f4f6;
    }
    .diagram-label {
        padding: 0.625rem;
        text-align: center;
        font-size: 0.875rem;
        font-weight: 600;
        color: #111827;
        background: #f9fafb;
    }
    .parts-detail {
        display: none;
        margin-top: 0.5rem;
        padding: 1.25rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: #f9fafb;
    }
    .parts-detail.show {
        display: block;
    }
    .diagram-detail-row {
        display: flex;
        gap: 1.25rem;
        align-items: flex-start;
    }
    .diagram-image-col,
    .parts-table-col {
        flex: 1;
        min-width: 0;
        max-width: 50%;
    }
    .full-diagram-img {
        width: 100%;
        height: auto;
        object-fit: contain;
        border-radius: 0.25rem;
        cursor: zoom-in;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.1);
        background: #fff;
    }
    @media (max-width: 768px) {
        .diagram-detail-row {
            flex-direction: column;
        }
        .diagram-image-col,
        .parts-table-col {
            max-width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const firstKey = @json($firstDiagramKey);

    function filterParts() {
        const url = new URL(window.location.href);
        url.searchParams.set('variation', $('#variation_filter').val());
        const search = $('#search-keyword').val().trim();
        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }
        window.location.href = url.toString();
    }

    $('#variation_filter').on('change', filterParts);
    $('#filter-parts-btn').on('click', filterParts);
    $('#search-keyword').on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            filterParts();
        }
    });

    $(document).on('click', '.diagram-card', function () {
        const diagKey = $(this).data('diag-key');
        if (!diagKey) {
            return;
        }

        $('.parts-detail').removeClass('show');
        $('.diagram-card').removeClass('active-diagram');
        $(this).addClass('active-diagram');

        const $detail = $('#detail-' + diagKey);
        if ($detail.length) {
            $detail.addClass('show');
            $detail[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    if (firstKey) {
        const $firstCard = $('.diagram-card[data-diag-key="' + firstKey + '"]');
        const $firstDetail = $('#detail-' + firstKey);
        if ($firstCard.length) {
            $firstCard.addClass('active-diagram');
        }
        if ($firstDetail.length) {
            $firstDetail.addClass('show');
        }
    }

    const $modal = $('#image-modal');
    const $modalImg = $('#modal-image');

    $(document).on('click', '[data-open-zoom]', function () {
        $modalImg.attr('src', $(this).data('open-zoom'));
        $modal.removeClass('hidden').addClass('flex').attr('aria-hidden', 'false');
    });

    function closeModal() {
        $modal.addClass('hidden').removeClass('flex').attr('aria-hidden', 'true');
        $modalImg.attr('src', '');
    }

    $('#close-image-modal').on('click', closeModal);
    $modal.on('click', function (event) {
        if (event.target === this) {
            closeModal();
        }
    });
    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && ! $modal.hasClass('hidden')) {
            closeModal();
        }
    });
})();
</script>
@endpush
