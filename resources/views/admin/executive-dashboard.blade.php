@extends('layouts.admin')

@section('title', 'Executive Dashboard')
@section('page-title', 'Executive Dashboard')

@section('content')
<div class="space-y-6">
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Executive Production Dashboard</h2>
                <p class="text-sm text-gray-500">Production value and employee output for {{ $periodLabel }}.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'all' => 'All Time'] as $key => $label)
                    <a href="{{ route('admin.executive-dashboard.index', ['period' => $key]) }}"
                       class="px-3 py-2 rounded-md text-sm font-semibold border {{ $period === $key ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
                <form method="GET" action="{{ route('admin.executive-dashboard.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="period" value="custom">
                    <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}" class="rounded-md border-gray-300 text-sm shadow-sm">
                    <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}" class="rounded-md border-gray-300 text-sm shadow-sm">
                    <button class="px-3 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold">Apply</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-5">
            <div class="rounded-lg border border-blue-100 bg-blue-50 p-5">
                <p class="text-sm font-medium text-blue-700">Total Units Processed</p>
                <p class="mt-2 text-3xl font-bold text-blue-950">{{ number_format($productionTotals['total_units']) }}</p>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50 p-5">
                <p class="text-sm font-medium text-emerald-700">Total MSRP Value</p>
                <p class="mt-2 text-3xl font-bold text-emerald-950">${{ number_format($productionTotals['total_msrp'], 2) }}</p>
            </div>
        </div>

        <div class="px-5 pb-5">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Employee Production Details</h3>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-sky-600 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Employee</th>
                            <th class="px-4 py-3 text-right font-semibold">Repairs</th>
                            <th class="px-4 py-3 text-right font-semibold">Repair MSRP</th>
                            <th class="px-4 py-3 text-right font-semibold">Tests</th>
                            <th class="px-4 py-3 text-right font-semibold">Test MSRP</th>
                            <th class="px-4 py-3 text-right font-semibold">Cleaned</th>
                            <th class="px-4 py-3 text-right font-semibold">Clean MSRP</th>
                            <th class="px-4 py-3 text-right font-semibold">Total MSRP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($productionRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $row->username }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row->units_repaired) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($row->msrp_repaired, 2) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row->units_tested) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($row->msrp_tested, 2) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($row->units_cleaned) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($row->msrp_cleaned, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">${{ number_format($row->total_msrp, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">No production activity found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-5 pb-5">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h3 class="text-base font-semibold text-gray-900">Executive Summary</h3>
                <div class="mt-3 space-y-2 text-sm text-gray-700">
                    @forelse($productionRows as $row)
                        @php
                            $summaryParts = [];
                            if ((int) $row->units_repaired > 0) {
                                $summaryParts[] = 'repaired '.number_format($row->units_repaired).' units at $'.number_format($row->msrp_repaired, 2);
                            }
                            if ((int) $row->units_tested > 0) {
                                $summaryParts[] = 'tested '.number_format($row->units_tested).' units at $'.number_format($row->msrp_tested, 2);
                            }
                            if ((int) $row->units_cleaned > 0) {
                                $summaryParts[] = 'cleaned and detailed '.number_format($row->units_cleaned).' units at $'.number_format($row->msrp_cleaned, 2);
                            }
                        @endphp
                        <p><span class="font-semibold text-gray-900">{{ $row->username }}</span> {{ implode(', ', $summaryParts) }}.</p>
                    @empty
                        <p>No repair, testing, or cleaning activity has been recorded for this period.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
