@extends('layouts.admin')

@section('title', 'Repair · '.($appliance->unit_label ?: '#'.$appliance->id))
@section('page-title', 'Repair / Triage')

@section('page-actions')
    <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back to unit</a>
    @if($repairResultCount > 0)
    <a href="{{ route('admin.inventory.repair-results.index', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Repair results ({{ $repairResultCount }})</a>
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
            <span class="inline-flex rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-900">Repair</span>
        </div>
    </div>

    @include('admin.inventory.partials.parts-panel', ['appearance' => 'card'])

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-cyan-700 px-5 py-3">
            <h2 class="text-lg font-semibold text-white">Diagnosis / repair log</h2>
        </div>
        <div class="p-5 space-y-4">
            @forelse($diagnoses as $entry)
            <div class="rounded-md border border-gray-200 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-gray-500">
                    <strong class="text-gray-900">{{ $entry['user_name'] ?? 'Unknown' }}</strong>
                    <span>{{ isset($entry['created_at']) ? \Illuminate\Support\Carbon::parse($entry['created_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i:s') : '—' }}</span>
                </div>
                <p class="mt-2 text-sm text-gray-800 whitespace-pre-wrap">{{ $entry['diagnosis'] ?? '' }}</p>
            </div>
            @empty
            <p class="text-sm text-gray-500">No diagnosis notes logged yet.</p>
            @endforelse

            @canAccess('appliance.edit')
            <form method="POST" action="{{ route('admin.inventory.repair.diagnosis.store', $appliance) }}" class="space-y-3 border-t border-gray-200 pt-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Add note / action taken</label>
                    <textarea name="diagnosis" rows="3" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" required placeholder="Describe the issue or repair action taken…">{{ old('diagnosis') }}</textarea>
                </div>
                <button type="submit" class="rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Add note</button>
            </form>
            @endcanAccess
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-red-700 px-5 py-3">
            <h2 class="text-lg font-semibold text-white">Failed testing steps — re-evaluation</h2>
        </div>
        <div class="p-5">
            @if(! $latestTest)
                <p class="text-sm text-gray-600">No testing result on file for this unit.</p>
            @elseif($failedSteps === [])
                <p class="text-sm text-gray-600">Latest testing result has no failed steps to re-test.</p>
                @if($latestTest['result_id'] ?? null)
                <a href="{{ route('admin.inventory.testing-results.show', [$appliance, $latestTest['result_id']]) }}" class="inline-flex mt-2 text-sm font-semibold text-blue-700 underline">View latest test</a>
                @endif
            @else
                <p class="text-sm text-gray-600 mb-4">
                    Re-test the failed steps from the latest testing run. All pass → <strong>Ready</strong>; any still fail → <strong>Demanufacture</strong>.
                </p>
                @canAccess('appliance.edit')
                <form method="POST" action="{{ route('admin.inventory.repair.reevaluation.store', $appliance) }}" class="space-y-4">
                    @csrf
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Failed step</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Original note</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Re-test</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Repair note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($failedSteps as $step)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $step['question'] }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $step['original_note'] ?: '—' }}</td>
                                    <td class="px-3 py-2">
                                        <select name="re_test[{{ $step['step_id'] }}]" class="w-full rounded-md border border-gray-300 px-2 py-1.5" required>
                                            <option value="no">Failed (needs more work)</option>
                                            <option value="yes">Passed (fixed)</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" name="re_note[{{ $step['step_id'] }}]" class="w-full rounded-md border border-gray-300 px-2 py-1.5" placeholder="Fix details">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="rounded-md bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Submit re-evaluation</button>
                </form>
                @else
                <p class="text-sm text-gray-500">You do not have permission to submit re-evaluations.</p>
                @endcanAccess
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('admin.inventory.partials.parts-search-script')
@endpush
