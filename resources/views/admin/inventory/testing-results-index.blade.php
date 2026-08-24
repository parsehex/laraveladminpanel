@extends('layouts.admin')

@section('title', 'Testing results · '.($appliance->unit_label ?: '#'.$appliance->id))
@section('page-title', 'Testing results')

@section('page-actions')
    <a href="{{ route('admin.inventory.show', $appliance) }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">Back to unit</a>
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-lg font-semibold text-gray-900">
            {{ $appliance->brand }} {{ $appliance->model?->model_number }}
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            {{ $appliance->category?->name ?: 'No category' }}
            · {{ count($results) }} completed {{ count($results) === 1 ? 'run' : 'runs' }}
        </p>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flow</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result status</th>
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
                        <td class="px-4 py-3 text-gray-700">
                            <code>{{ $row['flow_slug'] }}</code> v{{ $row['flow_version'] }}
                        </td>
                        <td class="px-4 py-3 text-gray-900 font-medium">{{ $row['resulting_status'] ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $row['user_name'] ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.inventory.testing-results.show', [$appliance, $row['result_id']]) }}" class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">No testing results for this unit yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
