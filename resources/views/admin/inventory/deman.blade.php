@extends('layouts.admin')

@section('title', 'Demanufacture · '.($appliance->unit_label ?: '#'.$appliance->id))
@section('page-title', 'Demanufacture')

@section('page-actions')
    <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back to unit</a>
    @if($testingResultCount > 0)
    <a href="{{ route('admin.inventory.testing-results.index', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700">Testing results ({{ $testingResultCount }})</a>
    @endif
    @if($repairResultCount > 0)
    <a href="{{ route('admin.inventory.repair-results.index', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-700">Repair results ({{ $repairResultCount }})</a>
    @endif
@endsection

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $appliance->brand }} {{ $appliance->model?->model_number }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $appliance->category?->name ?: 'No category' }}
                    · Serial {{ $appliance->serial_number ?: '—' }}
                    · Label {{ $appliance->unit_label ?: '—' }}
                </p>
            </div>
            <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-900">Demanufacture</span>
        </div>
    </div>

    @if($failedSteps !== [] || $repairSteps !== [])
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-slate-700 px-5 py-3">
            <h2 class="text-lg font-semibold text-white">Testing &amp; repair context</h2>
        </div>
        <div class="p-5 space-y-6">
            @if($failedSteps !== [])
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Failed testing steps (latest run)</h3>
                <div class="overflow-x-auto rounded-md border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Step</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($failedSteps as $step)
                            <tr>
                                <td class="px-4 py-2 text-gray-900">{{ $step['question'] }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $step['note'] ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($latestTest['result_id'] ?? null)
                <a href="{{ route('admin.inventory.testing-results.show', [$appliance, $latestTest['result_id']]) }}" class="inline-flex mt-2 text-sm font-semibold text-blue-700 underline">View full testing result</a>
                @endif
            </div>
            @endif

            @if($repairSteps !== [])
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Latest repair re-evaluation</h3>
                <div class="overflow-x-auto rounded-md border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Step</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Re-test</th>
                                <th class="px-4 py-2 text-left font-semibold text-gray-700">Note</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($repairSteps as $step)
                            <tr class="{{ $step['failed'] ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-2 text-gray-900">{{ $step['question'] }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $step['answer'] }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $step['note'] ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($latestRepair['result_id'] ?? null)
                <a href="{{ route('admin.inventory.repair-results.show', [$appliance, $latestRepair['result_id']]) }}" class="inline-flex mt-2 text-sm font-semibold text-blue-700 underline">View repair re-evaluation</a>
                @endif
            </div>
            @endif
        </div>
    </div>
    @endif

    @if($existingParts->isNotEmpty())
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-red-700 px-5 py-3">
            <h2 class="text-lg font-semibold text-white">Previously logged parts ({{ $existingParts->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Part #</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Description</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Price</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Condition</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Logged</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($existingParts as $part)
                    <tr>
                        <td class="px-4 py-3 font-mono text-gray-900">{{ $part->part_number }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $part->description }}</td>
                        <td class="px-4 py-3 text-right text-gray-900">${{ number_format($part->price, 2) }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $part->condition }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $part->created_at?->format('Y-m-d H:i') }}
                            @if($part->user)
                            · {{ $part->user->name }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @canAccess('appliance.edit')
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-red-700 px-5 py-3">
            <h2 class="text-lg font-semibold text-white">
                Log salvaged parts
                @if($prompts !== [])
                · {{ $appliance->category?->name }}
                @endif
            </h2>
        </div>
        <form method="POST" action="{{ route('admin.inventory.deman.store', $appliance) }}" class="p-5 space-y-5" id="deman-form">
            @csrf

            @if($prompts === [])
            <p class="text-sm text-gray-600">No category-specific prompts configured. Add custom parts below.</p>
            @canAccess('deman-flows.manage')
            <a href="{{ route('admin.deman-flows.index') }}" class="inline-flex text-sm font-semibold text-red-800 underline">Manage deman flows</a>
            @endcanAccess
            @else
            <div class="space-y-4">
                @foreach($prompts as $key => $description)
                <div class="rounded-lg border border-gray-200 p-4 space-y-3">
                    <label class="block text-sm font-semibold text-gray-900">{{ $description }}</label>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Part #</label>
                            <input
                                type="text"
                                name="prompts[{{ $key }}][part_number]"
                                value="{{ old('prompts.'.$key.'.part_number') }}"
                                class="caps w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono"
                                pattern="[A-Z0-9-]+"
                                title="Use only capital letters, numbers, and hyphens."
                                autocapitalize="characters"
                                spellcheck="false"
                                placeholder="Part #"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Price</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="prompts[{{ $key }}][price]"
                                value="{{ old('prompts.'.$key.'.price') }}"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                placeholder="0.00"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Condition</label>
                            <select name="prompts[{{ $key }}][condition]" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                                @foreach(\App\Models\DemanPart::CONDITIONS as $condition)
                                <option value="{{ $condition }}" @selected(old('prompts.'.$key.'.condition', 'Good') === $condition)>{{ $condition }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <div>
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Custom parts</h3>
                    <button type="button" id="add-custom-part" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Add custom part</button>
                </div>
                <div id="custom-parts-container" class="space-y-4"></div>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <button type="submit" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Save demanufacture parts</button>
            </div>
        </form>
    </div>
    @else
    <div class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">
        You do not have permission to log salvaged parts.
    </div>
    @endcanAccess
</div>
@endsection

@push('scripts')
<script>
(function () {
    let customIndex = 0;
    const container = document.getElementById('custom-parts-container');
    const addButton = document.getElementById('add-custom-part');
    if (!container || !addButton) {
        return;
    }

    const conditions = @json(\App\Models\DemanPart::CONDITIONS);

    function conditionOptions(selected) {
        return conditions.map(function (value) {
            const isSelected = value === (selected || 'Good') ? ' selected' : '';
            return '<option value="' + value + '"' + isSelected + '>' + value + '</option>';
        }).join('');
    }

    function addCustomPart(values) {
        const index = customIndex++;
        const row = document.createElement('div');
        row.className = 'custom-part rounded-lg border border-gray-200 p-4 space-y-3';
        row.innerHTML = `
            <div class="flex items-center justify-between gap-3">
                <label class="text-sm font-semibold text-gray-900">Custom part</label>
                <button type="button" class="remove-custom-part text-sm font-semibold text-red-700 hover:text-red-900">Remove</button>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">Description</label>
                <input type="text" name="custom_parts[${index}][description]" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Description" value="${values?.description || ''}">
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Part #</label>
                    <input type="text" name="custom_parts[${index}][part_number]" class="caps w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono" pattern="[A-Z0-9-]+" title="Use only capital letters, numbers, and hyphens." autocapitalize="characters" spellcheck="false" placeholder="Part #" value="${values?.part_number || ''}">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Price</label>
                    <input type="number" step="0.01" min="0" name="custom_parts[${index}][price]" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="0.00" value="${values?.price || ''}">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Condition</label>
                    <select name="custom_parts[${index}][condition]" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">${conditionOptions(values?.condition)}</select>
                </div>
            </div>
        `;
        container.appendChild(row);
        row.querySelector('.remove-custom-part').addEventListener('click', function () {
            row.remove();
        });
    }

    addButton.addEventListener('click', function () {
        addCustomPart();
    });

    @if(is_array(old('custom_parts')))
        @foreach(old('custom_parts') as $customPart)
        addCustomPart(@json($customPart));
        @endforeach
    @endif
})();
</script>
@endpush
