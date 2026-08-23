@extends('layouts.admin')

@section('title', 'Repair results · '.($appliance->unit_label ?: '#'.$appliance->id))
@section('page-title', 'Repair results')

@section('page-actions')
    <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back to unit</a>
    @if($appliance->status === 'Repair')
    <a href="{{ route('admin.inventory.repair.show', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-700">Open repair</a>
    @endif
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-lg font-semibold text-gray-900">{{ $appliance->brand }} {{ $appliance->model?->model_number }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ count($results) }} re-evaluation {{ count($results) === 1 ? 'run' : 'runs' }}</p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source test</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">By</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($results as $row)
                    <tr>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $row['completed_at'] ? \Illuminate\Support\Carbon::parse($row['completed_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $row['resulting_status'] ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($row['source_testing_result_id'])
                            <code class="text-xs">{{ $row['source_testing_result_id'] }}</code>
                            @else
                            —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $row['user_name'] ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.inventory.repair-results.show', [$appliance, $row['result_id']]) }}" class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No repair re-evaluations for this unit yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
