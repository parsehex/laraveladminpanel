@extends('layouts.admin')

@section('title', 'Repair result · '.($appliance->unit_label ?: '#'.$appliance->id))
@section('page-title', 'Repair re-evaluation result')

@section('page-actions')
    <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back to unit</a>
    @if(($repairResultCount ?? 1) > 1)
    <a href="{{ route('admin.inventory.repair-results.index', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">All repair results</a>
    @endif
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $appliance->brand }} {{ $appliance->model?->model_number }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Re-evaluation
                    · Completed {{ isset($result['completed_at']) ? \Illuminate\Support\Carbon::parse($result['completed_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                    By {{ $result['user_name'] ?? 'Unknown' }}
                    @if($result['source_testing_result_id'] ?? null)
                        · From test <code class="text-xs">{{ $result['source_testing_result_id'] }}</code>
                    @endif
                </p>
            </div>
            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-900">
                → {{ $result['resulting_status'] ?? '—' }}
            </span>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-red-700 px-5 py-3">
            <h2 class="text-lg font-semibold text-white">Re-tested steps</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($steps as $step)
            <div class="p-4 {{ $step['failed'] ? 'bg-amber-50' : '' }}">
                <p class="text-sm font-semibold text-gray-900">{{ $step['question'] }}</p>
                @if($step['original_note'] !== '')
                <p class="mt-1 text-xs text-gray-500">Original note: {{ $step['original_note'] }}</p>
                @endif
                <p class="mt-1 text-sm text-gray-800"><span class="font-medium">Re-test:</span> {{ $step['answer'] }}</p>
                @if($step['note'] !== '')
                <p class="mt-1 text-sm text-gray-600"><span class="font-medium">Repair note:</span> {{ $step['note'] }}</p>
                @endif
            </div>
            @empty
            <div class="p-6 text-center text-gray-500">No re-tested steps recorded.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
