@php
    use App\Enums\TimeActivity;
    use App\Models\TimeEntry;

    $assemblyEntries = $appliance->timeEntries
        ->filter(fn (TimeEntry $entry) => $entry->activity === TimeActivity::Assembly)
        ->values();
    $openSession = $assemblyEntries->first(
        fn (TimeEntry $entry) => $entry->ended_at === null && (int) $entry->user_id === (int) auth()->id()
    );
    $personSeconds = (int) $assemblyEntries->sum(fn (TimeEntry $entry) => $entry->durationSeconds() ?? 0);
    $assembled = $appliance->furnitureDetails?->isAssembled() ?? false;
@endphp

<section class="legacy-panel">
    <div class="legacy-panel-heading bg-emerald-700">Assembly</div>
    <div class="legacy-panel-body space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                @if($assembled)
                    <p class="font-semibold text-gray-900">Assembled</p>
                    <p class="text-gray-600">
                        {{ $appliance->furnitureDetails->assembled_at?->format('Y-m-d H:i:s') }}
                        @if($appliance->furnitureDetails->assembled_by_name)
                            by {{ $appliance->furnitureDetails->assembled_by_name }}
                        @endif
                    </p>
                @else
                    <p class="font-semibold text-gray-900">Not assembled</p>
                @endif
            </div>
            @canAccess('appliance.edit')
                @if($assembled)
                    <form method="POST" action="{{ route('admin.inventory.assembly.assembled.destroy', $appliance) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="legacy-btn bg-gray-500">Clear assembled</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.inventory.assembly.assembled.store', $appliance) }}">
                        @csrf
                        <button type="submit" class="legacy-btn bg-emerald-700">Mark assembled</button>
                    </form>
                @endif
            @endcanAccess
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <p class="font-semibold text-gray-900">Person-time {{ TimeEntry::formatSeconds($personSeconds) }}</p>
            @canAccess('appliance.edit')
                <form method="POST" action="{{ route('admin.inventory.assembly.clock-in', $appliance) }}">
                    @csrf
                    <button type="submit" class="legacy-btn bg-emerald-600">Clock in</button>
                </form>
                @if($openSession)
                    <form method="POST" action="{{ route('admin.inventory.assembly.clock-out', $appliance) }}">
                        @csrf
                        <button type="submit" class="legacy-btn bg-amber-600">Clock out</button>
                    </form>
                @endif
            @endcanAccess
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Clock in</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Clock out</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($assemblyEntries as $entry)
                        <tr>
                            <td class="px-3 py-2">{{ $entry->user_name }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $entry->started_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $entry->ended_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ $entry->durationSeconds() === null ? 'In progress' : TimeEntry::formatSeconds($entry->durationSeconds()) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-gray-500">No assembly time yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
