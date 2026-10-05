@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm"
             x-data="{
                open: localStorage.getItem('dashboardOperationsOpen') !== '0',
                toggle() {
                    this.open = !this.open;
                    localStorage.setItem('dashboardOperationsOpen', this.open ? '1' : '0');
                },
             }">
            <div class="px-5 py-4 border-b border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <button type="button"
                            class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                            @click="toggle()"
                            :aria-expanded="open.toString()"
                            aria-controls="operations-panel"
                            :aria-label="open ? 'Collapse operations' : 'Expand operations'">
                        <i class="fas text-sm" :class="open ? 'fa-minus' : 'fa-plus'"></i>
                    </button>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Ben's Appliances Operations</h2>
                        <p class="text-sm text-gray-500">Showing {{ $periodLabel }}.</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'all' => 'All'] as $key => $label)
                        <a href="{{ route('admin.dashboard', ['period' => $key]) }}"
                        class="px-3 py-2 rounded-md text-sm font-semibold border {{ $period === $key ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="period" value="custom">
                        <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}" class="rounded-md border-gray-300 text-sm shadow-sm">
                        <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}" class="rounded-md border-gray-300 text-sm shadow-sm">
                        <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold">Apply</button>
                    </form>
                </div>
            </div>

            <div id="operations-panel" x-show="open" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 p-5">
                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                        <p class="text-sm font-medium text-blue-700">Units Added</p>
                        <p class="mt-2 text-3xl font-bold text-blue-950">{{ number_format($stats['total_units']) }}</p>
                    </div>
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-4">
                        <p class="text-sm font-medium text-emerald-700">Inventory Value</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-950">${{ number_format($stats['inventory_value'], 2) }}</p>
                        <p class="mt-1 text-xs text-emerald-800/80">Cost of units added in this period that are still on hand (price + parts).</p>
                    </div>
                    <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                        <p class="text-sm font-medium text-amber-700">Sold Units</p>
                        <p class="mt-2 text-3xl font-bold text-amber-950">{{ number_format($stats['sold_units']) }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-medium text-slate-700">Sales Total</p>
                        <p class="mt-2 text-3xl font-bold text-slate-950">${{ number_format($stats['sales_total'], 2) }}</p>
                    </div>
                </div>

                <div class="border-t border-gray-200">
                    <div class="px-5 py-3">
                        <h3 class="text-sm font-semibold text-gray-900">User Activity</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap">User</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Trucks Added</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Trucks Deleted</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Units Added</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Units Deleted</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">MSRP Added</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Tested</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Deman.</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Repaired</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Showroom</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Sales</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($activityRows as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap">{{ $row['username'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['trucks_added'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['trucks_deleted'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['units_added'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['units_deleted'] }}</td>
                                        <td class="px-4 py-3 text-right">${{ number_format($row['total_msrp_added'], 2) }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['units_tested'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['demanufactured'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['repaired'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['showroom_sent'] }}</td>
                                        <td class="px-4 py-3 text-right">{{ $row['sales_marked'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="px-4 py-8 text-center text-gray-500">No staff activity found for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Appliances Holding for Parts</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Model #</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Serial #</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Notes</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Set By</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Parts Ordered</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($holdingForParts as $appliance)
                            @php
                                $history = $appliance->statusHistories->sortByDesc('created_at')->first();
                            @endphp
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $appliance->model?->model_number ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $appliance->serial_number ?: '-' }}</td>
                                <td class="px-4 py-3 max-w-xs text-gray-600">{{ $history?->notes ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $history?->user?->name ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold {{ $history?->parts_ordered ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $history?->parts_ordered ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.inventory.show', $appliance) }}"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100"
                                       title="View details"
                                       aria-label="View details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">No appliances are currently holding for parts.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Appliances Holding</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Model #</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Serial #</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Notes</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Set By</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($holding as $appliance)
                            @php
                                $history = $appliance->statusHistories->sortByDesc('created_at')->first();
                            @endphp
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $appliance->model?->model_number ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $appliance->serial_number ?: '-' }}</td>
                                <td class="px-4 py-3 max-w-xs text-gray-600">{{ $history?->notes ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $history?->user?->name ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.inventory.show', $appliance) }}"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100"
                                       title="View details"
                                       aria-label="View details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No appliances are currently holding.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $isInputRequestTab = $feedbackKind === \App\Models\Suggestion::KIND_INPUT_REQUEST;
        $suggestionFilterQuery = array_filter([
            'period' => request('period'),
            'from' => request('from'),
            'to' => request('to'),
            'feedback_kind' => $feedbackKind,
        ]);
        $kindTabQuery = array_filter([
            'period' => request('period'),
            'from' => request('from'),
            'to' => request('to'),
            'suggestion_status' => $suggestionStatus,
        ]);
    @endphp
    <div id="suggestions" class="bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-gray-900">Website Feedback</h3>
                <div class="flex gap-2">
                    <a href="{{ route('admin.dashboard', array_merge($suggestionFilterQuery, ['suggestion_status' => 'pending'])) }}"
                       class="px-3 py-1.5 rounded-md text-sm font-semibold border {{ $suggestionStatus === 'pending' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        Pending
                    </a>
                    <a href="{{ route('admin.dashboard', array_merge($suggestionFilterQuery, ['suggestion_status' => 'completed'])) }}"
                       class="px-3 py-1.5 rounded-md text-sm font-semibold border {{ $suggestionStatus === 'completed' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        Completed
                    </a>
                    <a href="{{ route('admin.dashboard', array_merge($suggestionFilterQuery, ['suggestion_status' => 'all'])) }}"
                       class="px-3 py-1.5 rounded-md text-sm font-semibold border {{ $suggestionStatus === 'all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        All
                    </a>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.dashboard', array_merge($kindTabQuery, ['feedback_kind' => \App\Models\Suggestion::KIND_SUGGESTION])) }}"
                   class="px-3 py-1.5 rounded-md text-sm font-semibold border {{ ! $isInputRequestTab ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    Feedback
                </a>
                <a href="{{ route('admin.dashboard', array_merge($kindTabQuery, ['feedback_kind' => \App\Models\Suggestion::KIND_INPUT_REQUEST])) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-semibold border {{ $isInputRequestTab ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    Input Requests
                    @if($pendingInputRequestCount > 0)
                        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-500 px-1.5 py-0.5 text-xs font-bold leading-none text-white">
                            {{ $pendingInputRequestCount }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
        <div class="p-5 space-y-5">
            @if($isInputRequestTab)
                @can('suggestions.complete')
                    <form method="POST" action="{{ route('admin.dashboard.input-requests.store') }}" class="space-y-3">
                        @csrf
                        <textarea name="suggestion" rows="3" required class="w-full rounded-md border-gray-300 shadow-md p-2" placeholder="Ask staff for input on a workflow, design choice, or open question...">{{ old('suggestion') }}</textarea>
                        <div>
                            <label for="input-request-page-url" class="mb-1 block text-sm font-medium text-gray-700">Related page URL</label>
                            <input
                                id="input-request-page-url"
                                type="url"
                                name="page_url"
                                value="{{ old('page_url', url()->full()) }}"
                                class="w-full rounded-md border-gray-300 text-sm shadow-sm"
                                placeholder="https://..."
                            >
                        </div>
                        <button class="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-semibold">Post input request</button>
                    </form>
                @endcan
            @else
                <form method="POST" action="{{ route('admin.dashboard.suggestions.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="page_url" value="{{ url()->full() }}">
                    <textarea name="suggestion" rows="3" required class="w-full rounded-md border-gray-300 shadow-md p-2" placeholder="Share a workflow issue, improvement, or dashboard request...">{{ old('suggestion') }}</textarea>
                    <div class="flex flex-wrap items-center gap-3">
                        <select name="urgency" class="rounded-md border-gray-300 text-sm shadow-sm p-1">
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="low">Low</option>
                        </select>
                        <button class="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-semibold">Submit Suggestion</button>
                    </div>
                </form>
            @endif

            <div class="space-y-3">
                @forelse($suggestions as $suggestion)
                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ $suggestion->username ?: $suggestion->user?->name ?: 'Staff' }}</span>
                                    @unless($suggestion->isInputRequest())
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $suggestion->urgency === 'high' ? 'bg-red-100 text-red-700' : ($suggestion->urgency === 'low' ? 'bg-gray-100 text-gray-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($suggestion->urgency) }}</span>
                                    @endunless
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $suggestion->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($suggestion->status) }}</span>
                                </div>
                                <p class="mt-2 text-sm text-gray-700">{{ $suggestion->suggestion }}</p>
                                @if($suggestion->page_url)
                                    <p class="mt-2 text-xs">
                                        <span class="font-medium text-gray-500">Page:</span>
                                        <a href="{{ $suggestion->page_url }}" class="text-blue-600 hover:text-blue-800 break-all">{{ $suggestion->page_url }}</a>
                                    </p>
                                @endif
                                <p class="mt-1 text-xs text-gray-500">{{ $suggestion->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                            @can('suggestions.complete')
                                @if($suggestion->status !== 'completed')
                                    <form method="POST" action="{{ route('admin.dashboard.suggestions.complete', $suggestion) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                                                title="Mark complete"
                                                aria-label="Mark complete">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                        @if(! empty($suggestion->responses))
                            <div class="mt-3 space-y-2">
                                @foreach($suggestion->responses as $response)
                                    <div class="rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                        <strong>{{ $response['user'] ?? 'Staff' }}:</strong> {{ $response['message'] ?? '' }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.dashboard.suggestions.responses.store', $suggestion) }}" class="mt-3 flex gap-2">
                            @csrf
                            <input name="response" class="flex-1 rounded-md border-gray-300 text-sm shadow-sm" placeholder="Add a response">
                            <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold">Reply</button>
                        </form>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500">
                        @if($isInputRequestTab)
                            @if($suggestionStatus === 'completed')
                                No completed input requests yet.
                            @elseif($suggestionStatus === 'all')
                                No input requests have been posted yet.
                            @else
                                No open input requests.
                            @endif
                        @elseif($suggestionStatus === 'completed')
                            No completed suggestions yet.
                        @elseif($suggestionStatus === 'all')
                            No suggestions have been submitted yet.
                        @else
                            No pending suggestions.
                        @endif
                    </div>
                @endforelse
            </div>

            @if($suggestions->hasPages())
                <div class="border-t border-gray-200 pt-4">
                    {{ $suggestions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
