@extends('layouts.admin')

@section('title', 'Deman Flows')
@section('page-title', 'Deman Flows')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-red-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold text-white">Category demanufacture prompts</h2>
            <span class="text-sm text-red-100">{{ count($flows) }} flows</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Prompts</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($flows as $flow)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $flow['name'] }}</td>
                        <td class="px-4 py-3 text-gray-600"><code>{{ $flow['slug'] }}</code></td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $flow['prompt_count'] }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $flow['updated_at'] ? \Illuminate\Support\Carbon::parse($flow['updated_at'])->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.deman-flows.edit', $flow['slug']) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No deman flows found. Run <code class="text-sm">php artisan deman-flows:import</code>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
