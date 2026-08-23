@extends('layouts.admin')

@section('title', 'Testing result · '.($appliance->unit_label ?: '#'.$appliance->id))
@section('page-title', 'Testing result')

@section('page-actions')
    <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back to unit</a>
    @if(($testingResultCount ?? 1) > 1)
    <a href="{{ route('admin.inventory.testing-results.index', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">All results</a>
    @endif
@endsection

@section('content')
@php
    $snapshot = $result['flow_snapshot'] ?? [];
    $completedAt = $result['completed_at'] ?? null;
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $appliance->brand }} {{ $appliance->model?->model_number }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $snapshot['name'] ?? ($result['flow_slug'] ?? 'Testing flow') }}
                    · Flow v{{ $result['flow_version'] ?? '?' }}
                    · Completed {{ $completedAt ? \Illuminate\Support\Carbon::parse($completedAt)->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                    By {{ $result['user_name'] ?? 'Unknown' }}
                    @if(! empty($result['notes']))
                        · Notes: {{ $result['notes'] }}
                    @endif
                </p>
            </div>
            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-900">
                → {{ $result['resulting_status'] ?? '—' }}
            </span>
        </div>
    </div>

    @if($failedSteps !== [])
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
        <p class="font-semibold text-amber-950">{{ count($failedSteps) }} Failed steps</p>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-5 py-3">
            <h2 class="text-lg font-semibold text-white">Checklist answers</h2>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse($steps as $step)
            <div class="p-4 {{ $step['failed'] ? 'bg-amber-50' : '' }}">
                <p class="text-sm font-semibold text-gray-900">{{ $step['question'] }}</p>
                <p class="mt-1 text-sm text-gray-800">
                    <span class="font-medium">Answer:</span> {{ $step['answer'] }}
                    @if($step['answer_key'] && $step['answer_key'] !== $step['answer'])
                        <span class="text-gray-500">({{ $step['answer_key'] }})</span>
                    @endif
                </p>
                @if($step['note'] !== '')
                <p class="mt-1 text-sm text-gray-600"><span class="font-medium">Note:</span> {{ $step['note'] }}</p>
                @endif
            </div>
            @empty
            <div class="p-6 text-center text-gray-500">No answers recorded.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
