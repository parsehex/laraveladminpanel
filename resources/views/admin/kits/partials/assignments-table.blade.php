@php
    $statusClasses = [
        'pending' => 'bg-amber-100 text-amber-800',
        'in_progress' => 'bg-cyan-100 text-cyan-800',
        'built' => 'bg-orange-100 text-orange-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
    ];
    $completed = $completed ?? false;
@endphp

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="{{ $headerClass }} px-6 py-4">
        <h2 class="text-xl font-semibold text-white">{{ $title }}</h2>
    </div>
    <div class="p-4">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Kit</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Cost</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Platform</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Due Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Notes</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($rows as $assignment)
                        @php($kitCost = ($kitSummaries[$assignment->kit_id]['cost'] ?? 0) * $assignment->quantity)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $assignment->kit?->code }} - {{ $assignment->kit?->name }}</td>
                            <td class="px-4 py-3 text-right">{{ $assignment->quantity }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($kitCost, 2) }}</td>
                            <td class="px-4 py-3">{{ ucfirst($assignment->platform) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$assignment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $assignment->due_date?->format('Y-m-d') ?: 'No due' }}</td>
                            <td class="px-4 py-3 max-w-xs">{{ $assignment->notes ?: 'N/A' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($assignment->status === 'pending' && ($assignment->assigned_to === auth()->id() || $canManage))
                                        <form method="POST" action="{{ route('admin.kits.assignments.start', $assignment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-cyan-700 font-semibold">Start</button>
                                        </form>
                                    @endif

                                    @if($assignment->status === 'in_progress' && ($assignment->assigned_to === auth()->id() || $canManage))
                                        <form method="POST" action="{{ route('admin.kits.assignments.built', $assignment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-emerald-700 font-semibold">Built</button>
                                        </form>
                                    @endif

                                    @if($showConfirm)
                                        <form method="POST" action="{{ route('admin.kits.assignments.confirm', $assignment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="text-emerald-700 font-semibold">Confirm</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.kits.index', ['assign' => $assignment->id]) }}" class="text-gray-700 font-semibold">Messages</a>
                                    <button type="button" data-sop-url="{{ route('admin.kits.sop', $assignment->kit) }}" class="text-blue-600 font-semibold">SOP</button>

                                    @if($canManage && ! $completed)
                                        <form method="POST" action="{{ route('admin.kits.assignments.destroy', $assignment) }}" onsubmit="return confirm('Delete this assignment? This will reverse deducted stock when needed.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 font-semibold">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No assignments.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
