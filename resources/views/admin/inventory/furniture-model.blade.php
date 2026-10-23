@extends('layouts.admin')

@php
    $pageHeading = $productName ?: ($model->model_number ?: 'Furniture model');
    $statusFilterUrl = function (string $status) use ($model, $selectedStatuses): string {
        $nextStatuses = in_array($status, $selectedStatuses, true)
            ? array_values(array_filter($selectedStatuses, fn (string $selected) => $selected !== $status))
            : [...$selectedStatuses, $status];

        return route('admin.inventory.furniture.show', [
            'model' => $model,
            ...collect(request()->query())->except(['status', 'page'])->all(),
            'status' => $nextStatuses ?: null,
        ]);
    };
    $quickActionConfig = [
        'availability' => $actionAvailability,
        'action' => old('action', \App\Enums\FurnitureQuickAction::ChangeStatus->value),
        'target' => old('status', in_array('Ready', $targetStatuses, true) ? 'Ready' : ($targetStatuses[0] ?? null)),
        'count' => max(1, (int) old('count', 1)),
        'price' => (string) old('sold_price', ''),
        'location' => (string) old('location', ''),
        'notes' => (string) old('notes', ''),
        'override' => (bool) old('override', false),
        'selected' => array_values(array_map('strval', (array) old('unit_ids', []))),
    ];
    $facts = [
        ['label' => 'Model #', 'value' => $model->model_number],
        ['label' => 'Brand', 'value' => $brand],
        ['label' => 'Category', 'value' => $model->category?->name],
        ['label' => 'MSRP', 'value' => $msrp > 0 ? '$'.number_format($msrp, 2) : null],
        ['label' => 'Assembled', 'value' => $assembledCount.' of '.$unitCount],
    ];
@endphp

@section('title', $pageHeading)
@section('page-title', 'Furniture Model')
@section('page-subtitle', trim(implode(' ', array_filter([$brand, $model->model_number]))))

@section('page-actions')
    <a href="{{ route('admin.inventory.furniture') }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
        Back to furniture
    </a>
@endsection

@section('content')
<div class="space-y-6">
    <section class="rounded-lg bg-white p-6 shadow">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-xl font-bold text-gray-900">{{ $pageHeading }}</h2>
                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    @foreach($facts as $fact)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $fact['label'] }}</dt>
                        <dd class="font-medium text-gray-900">{{ $fact['value'] ?: '—' }}</dd>
                    </div>
                    @endforeach
                </dl>
            </div>
            <div class="text-right">
                <div class="text-4xl font-extrabold leading-none text-gray-900">{{ $unitCount }}</div>
                <div class="mt-1 text-sm font-medium text-gray-500">{{ \Illuminate\Support\Str::plural('unit', $unitCount) }} in inventory</div>
            </div>
        </div>
        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4">
            @foreach($statusCounts as $statusCount)
            <x-admin.status-chip
                :status="$statusCount['status']"
                :count="$statusCount['count']"
                :href="$statusFilterUrl($statusCount['status'])"
                :selected="in_array($statusCount['status'], $selectedStatuses, true)"
            />
            @endforeach
            @if($selectedStatuses !== [])
            <a href="{{ route('admin.inventory.furniture.show', $model) }}" class="text-sm font-medium text-blue-700 hover:underline">Clear filter</a>
            @endif
        </div>
    </section>

    <section class="overflow-hidden rounded-lg bg-white shadow">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="font-semibold text-gray-900"><i class="fas fa-truck mr-2 text-gray-500"></i>By truck</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Truck</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Units</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Statuses</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($truckBreakdown as $truckRow)
                    <tr>
                        <td class="px-6 py-3 text-sm">
                            <a href="{{ route('admin.trucks.show', $truckRow['truck_id']) }}" class="font-semibold text-blue-700 hover:underline">{{ $truckRow['truck_name'] }}</a>
                        </td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ $truckRow['count'] }}</td>
                        <td class="px-6 py-3">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($truckRow['statuses'] as $statusCount)
                                <x-admin.status-chip :status="$statusCount['status']" :count="$statusCount['count']" />
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg bg-white shadow" x-data="furnitureQuickActions(@js($quickActionConfig))">
        @canAccess('appliance.edit')
        <form method="POST" action="{{ route('admin.inventory.furniture.actions', $model) }}" class="border-b border-gray-200 bg-slate-50 px-6 py-4" x-on:submit="submitting = true">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="unit_ids[]" :value="id">
            </template>
            <input type="hidden" name="action" :value="action">

            <div class="flex flex-wrap items-center gap-2">
                <span class="mr-1 text-sm font-semibold text-gray-700"><i class="fas fa-bolt mr-1.5 text-amber-500"></i>Quick action</span>
                @foreach(\App\Enums\FurnitureQuickAction::cases() as $quickAction)
                <button type="button"
                        x-on:click="action = @js($quickAction->value)"
                        :class="action === @js($quickAction->value) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        class="rounded-md border px-3 py-1.5 text-sm font-semibold">
                    {{ $quickAction->label() }}
                </button>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-4">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">How many</span>
                    <input type="number" name="count" x-model.number="count" :min="Math.max(1, selected.length)" max="1000" required
                           class="w-24 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </label>

                <label class="block" x-show="action === 'status'" x-cloak>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">New status</span>
                    <select name="status" x-model="target" :disabled="action !== 'status'"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($targetStatuses as $targetStatus)
                        <option value="{{ $targetStatus }}">{{ $targetStatus }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block" x-show="action === 'sell'" x-cloak>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Sale price per unit</span>
                    <span class="relative block">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-gray-500">$</span>
                        <input type="number" name="sold_price" x-model="price" step="0.01" min="0" :disabled="action !== 'sell'" placeholder="0.00"
                               class="w-36 rounded-md border border-gray-300 py-2 pl-7 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </span>
                </label>
                <p class="pb-2 text-sm text-gray-600" x-show="action === 'sell'" x-cloak>
                    <span x-text="count || 0"></span> × <span x-text="money(price)"></span> = <strong class="text-gray-900" x-text="money((count || 0) * (Number(price) || 0))"></strong> total
                </p>

                <label class="block" x-show="action === 'location'" x-cloak>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Location</span>
                    <input type="text" name="location" x-model="location" list="furniture-model-locations" maxlength="255" :disabled="action !== 'location'" placeholder="Leave blank to clear"
                           class="w-56 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="furniture-model-locations">
                        @foreach($locations as $location)
                        <option value="{{ $location }}"></option>
                        @endforeach
                    </datalist>
                </label>

                <label class="block" x-show="action === 'sell' || action === 'status'" x-cloak>
                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Notes (optional)</span>
                    <input type="text" name="notes" x-model="notes" maxlength="1000" :disabled="action !== 'sell' && action !== 'status'"
                           class="w-64 rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </label>

                <button type="submit" :disabled="! canSubmit"
                        class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-text="submitLabel"></span>
                </button>
            </div>

            <p class="mt-3 text-sm text-gray-600" x-show="action === 'sell'" x-cloak>
                Enter what one unit sold for, before tax. Each unit is recorded at this price.
            </p>
            <p class="mt-2 text-sm text-gray-700">
                <strong x-text="stats.eligible"></strong> eligible <span class="text-gray-500">(units <span x-text="stats.rule"></span>)</span>
                <template x-if="selected.length">
                    <span>· <span x-text="selected.length"></span> checked units are used first</span>
                </template>
            </p>
            <div x-show="needsOverride && ! impossible" x-cloak class="mt-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                <p>Only <strong x-text="stats.eligible"></strong> eligible. Lower the count, or override the status rules to also use units <span x-text="stats.override_rule"></span> (<span x-text="stats.override"></span> available).</p>
                <label class="mt-1.5 inline-flex items-center gap-2 font-semibold">
                    <input type="checkbox" name="override" value="1" x-model="override" class="h-4 w-4 rounded border-amber-400">
                    Override status rules for this action
                </label>
            </div>
            <p x-show="impossible" x-cloak class="mt-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                Only <strong x-text="stats.override"></strong> units of this model can take this action, even with an override. Lower the count.
            </p>
            @foreach(['count', 'unit_ids', 'status', 'sold_price', 'location', 'notes', 'action'] as $field)
            @error($field)
            <p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
            @enderror
            @endforeach
        </form>
        @endcanAccess
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <h3 class="font-semibold text-gray-900">
                <i class="fas fa-couch mr-2 text-gray-500"></i>Units
                @if($selectedStatuses !== [])
                <span class="ml-1 text-sm font-normal text-gray-500">({{ count($shownUnitIds) }} matching {{ implode(', ', $selectedStatuses) }})</span>
                @endif
            </h3>
            <div class="flex flex-wrap gap-2">
                <button type="button"
                        x-bind:disabled="selected.length === 0"
                        x-on:click="window.open(@js(route('admin.inventory.stickers')) + '?ids=' + selected.join(','), '_blank')"
                        class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <i class="fas fa-qrcode mr-2"></i>Print selected stickers <span class="ml-1" x-show="selected.length" x-text="'(' + selected.length + ')'"></span>
                </button>
                <a href="{{ route('admin.inventory.stickers', ['ids' => implode(',', $shownUnitIds)]) }}"
                   target="_blank"
                   class="inline-flex items-center justify-center rounded-md bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-teal-700">
                    <i class="fas fa-tags mr-2"></i>Print all {{ count($shownUnitIds) }} stickers
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox"
                                   aria-label="Select all units on this page"
                                   x-on:change="const pageIds = [...$root.querySelectorAll('[data-unit-checkbox]')].map((box) => box.value); selected = $event.target.checked ? [...new Set([...selected, ...pageIds])] : selected.filter((id) => ! pageIds.includes(id))">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit Label</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Truck</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Condition</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Assembled</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Received</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($units as $unit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input type="checkbox" data-unit-checkbox value="{{ $unit->id }}" x-model="selected" aria-label="Select {{ $unit->unit_label ?: 'unit '.$unit->id }}">
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm">
                            <a href="{{ route('admin.inventory.show', $unit) }}" class="font-semibold text-blue-700 hover:underline">{{ $unit->unit_label ?: 'Unit #'.$unit->id }}</a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $unit->truck?->name ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3"><x-admin.status-chip :status="$unit->status" /></td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $unit->location ?: '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $unit->receiving_condition ?: '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $unit->furnitureDetails?->isAssembled() ? 'Yes' : 'No' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700" title="{{ $unit->created_at?->format('Y-m-d H:i:s') }}">{{ $unit->created_at?->format('m/d/y') ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <a href="{{ route('admin.inventory.show', $unit) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100" title="View details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.inventory.stickers', ['ids' => $unit->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100" title="Print sticker">
                                <i class="fas fa-qrcode"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">No units match this filter.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-admin.table-pagination :paginator="$units" />
    </section>
</div>
@endsection

@push('scripts')
<script>
    window.furnitureQuickActions = function (config) {
        return {
            ...config,
            submitting: false,
            init() {
                this.$watch('selected', (ids) => {
                    if ((this.count || 0) < ids.length) {
                        this.count = ids.length;
                    }
                });
            },
            get stats() {
                const key = this.action === 'status' ? 'status:' + this.target : this.action;

                return this.availability[key] ?? { eligible: 0, override: 0, rule: '', override_rule: '' };
            },
            get needsOverride() {
                return (this.count || 0) > this.stats.eligible;
            },
            get impossible() {
                return (this.count || 0) > this.stats.override;
            },
            get canSubmit() {
                if (this.submitting || (this.count || 0) < Math.max(1, this.selected.length) || this.impossible) {
                    return false;
                }

                if (this.needsOverride && ! this.override) {
                    return false;
                }

                return this.action !== 'sell' || this.price !== '';
            },
            get submitLabel() {
                const units = (this.count || 0) + ' ' + ((this.count || 0) === 1 ? 'unit' : 'units');

                return {
                    sell: 'Sell ' + units,
                    status: 'Move ' + units + ' to ' + this.target,
                    assemble: 'Mark ' + units + ' assembled',
                    location: 'Set location on ' + units,
                }[this.action] ?? 'Apply';
            },
            money(value) {
                return '$' + (Number(value) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    };
</script>
@endpush
