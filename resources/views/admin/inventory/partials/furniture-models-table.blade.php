@php
    $statusChipQuery = function (string $status) use ($selectedStatuses): array {
        $nextStatuses = in_array($status, $selectedStatuses, true)
            ? array_values(array_filter($selectedStatuses, fn (string $selected) => $selected !== $status))
            : [...$selectedStatuses, $status];

        return [
            ...collect(request()->query())->except(['status', 'page'])->all(),
            'status' => $nextStatuses ?: null,
        ];
    };
@endphp
<x-admin.data-table density="sheet" :table="$dataTable">
    <x-slot:header>
        @include('admin.inventory.partials.list-view-toggle')
    </x-slot:header>
    <x-slot:filters>
        @include('admin.inventory.partials.filters')
    </x-slot:filters>

    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 sticky-table-head">
            <tr>
                <x-admin.data-table.header-cells :data-table="$dataTable" :sort="$sort" :direction="$direction" />
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($items as $group)
            @php
                $groupKey = \App\Support\FurnitureModelGroups::key($group->model_id);
                $modelUrl = $group->model_id !== null ? route('admin.inventory.furniture.show', $group->model_id) : null;
            @endphp
            <tr class="hover:bg-gray-50">
                <x-admin.data-table.cell column="product" class="min-w-[18rem] max-w-[28rem] whitespace-normal!">
                    @if($modelUrl)
                    <a href="{{ $modelUrl }}" class="font-semibold text-blue-700 hover:underline">{{ $group->group_product_name ?: 'Unnamed model' }}</a>
                    @else
                    <span class="font-semibold text-gray-900">No model assigned</span>
                    <span class="block text-xs text-gray-500">Switch to Units to see these items.</span>
                    @endif
                </x-admin.data-table.cell>
                <x-admin.data-table.cell column="model">{{ $group->group_model_number ?: '-' }}</x-admin.data-table.cell>
                <x-admin.data-table.cell column="brand" truncate title="{{ $group->group_brand ?: '-' }}">{{ $group->group_brand ?: '-' }}</x-admin.data-table.cell>
                <x-admin.data-table.cell column="category" truncate title="{{ $group->group_category ?: '-' }}">{{ $group->group_category ?: '-' }}</x-admin.data-table.cell>
                <x-admin.data-table.cell column="units" align="right" class="text-base font-bold text-gray-900">{{ $group->unit_count }}</x-admin.data-table.cell>
                <x-admin.data-table.cell column="statuses" class="min-w-[14rem] whitespace-normal!">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($groupStatuses->get($groupKey, []) as $statusCount)
                        <x-admin.status-chip
                            :status="$statusCount['status']"
                            :count="$statusCount['count']"
                            :href="route($listRoute, $statusChipQuery($statusCount['status']))"
                            :selected="in_array($statusCount['status'], $selectedStatuses, true)"
                        />
                        @endforeach
                    </div>
                </x-admin.data-table.cell>
                <x-admin.data-table.cell column="trucks" class="min-w-[10rem] whitespace-normal!">
                    <div class="flex flex-col gap-0.5">
                        @foreach($groupTrucks->get($groupKey, []) as $truckCount)
                        <a href="{{ route('admin.trucks.show', $truckCount['truck_id']) }}" class="text-blue-700 hover:underline">
                            {{ $truckCount['truck_name'] }} <span class="text-gray-500">({{ $truckCount['count'] }})</span>
                        </a>
                        @endforeach
                    </div>
                </x-admin.data-table.cell>
                <x-admin.data-table.cell column="last_received" title="{{ $group->last_received_at?->format('Y-m-d H:i:s') ?? 'N/A' }}">{{ $group->last_received_at?->format('m/d/y') ?? 'N/A' }}</x-admin.data-table.cell>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-6 py-8 text-center text-gray-500">No furniture found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <x-slot:footer>
    <x-admin.table-pagination :paginator="$items" />
    </x-slot:footer>
</x-admin.data-table>
