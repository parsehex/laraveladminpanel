@extends('layouts.admin')

@section('title', 'Testing Flows')
@section('page-title', 'Testing Flows')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Category testing checklists</h2>
            <span class="text-sm text-blue-100">{{ count($flows) }} flows</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Steps</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Version</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($flows as $flow)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $flow['name'] }}</td>
                        <td class="px-4 py-3 text-gray-600"><code>{{ $flow['slug'] }}</code></td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $flow['step_count'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">v{{ $flow['version'] }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $flow['updated_at'] ? \Illuminate\Support\Carbon::parse($flow['updated_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.testing-flows.edit', $flow['slug']) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No testing flows found. Run <code class="text-sm">php artisan testing-flows:import</code>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
