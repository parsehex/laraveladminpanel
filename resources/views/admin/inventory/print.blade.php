<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>Ben's Appliances Inventory Print</title>
    <style>
        :root {
            --ink: #16202a;
            --muted: #667085;
            --line: #d9e2ec;
            --soft: #f6f8fb;
            --brand: #0b5cab;
            --gold: #c58a00;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef2f6;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .sheet {
            width: 8.5in;
            min-height: 11in;
            margin: 20px auto;
            padding: 0.45in;
            background: #fff;
            page-break-after: always;
            border: 1px solid var(--line);
        }

        .header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 18px;
            align-items: start;
            border-bottom: 3px solid var(--brand);
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .company {
            font-size: 29px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0;
            color: var(--brand);
        }

        .subtitle {
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.1px;
        }

        .doc-box {
            min-width: 188px;
            border: 1px solid var(--line);
            background: var(--soft);
            padding: 10px 12px;
            text-align: right;
        }

        .doc-title {
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.9px;
        }

        .doc-number {
            margin-top: 3px;
            font-size: 24px;
            font-weight: 800;
        }

        .badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 0 0 16px;
        }

        .badge {
            border: 1px solid var(--line);
            background: #fff;
            padding: 6px 9px;
            font-weight: 700;
        }

        .badge strong {
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            margin-right: 5px;
        }

        .status-badge {
            border-color: #b9d6ff;
            color: #084b93;
            background: #eef6ff;
        }

        .grade-badge {
            border-color: #f5d57d;
            color: #805700;
            background: #fff8df;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        .panel {
            border: 1px solid var(--line);
            background: #fff;
        }

        .panel-title {
            padding: 8px 10px;
            background: var(--brand);
            color: #fff;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-size: 11px;
        }

        .panel-body { padding: 10px; }

        .details {
            width: 100%;
            border-collapse: collapse;
        }

        .details th,
        .details td {
            border-bottom: 1px solid #eef1f5;
            padding: 7px 0;
            vertical-align: top;
        }

        .details th {
            width: 36%;
            text-align: left;
            color: var(--muted);
            font-weight: 700;
        }

        .details td {
            text-align: right;
            font-weight: 700;
        }

        .wide { grid-column: 1 / -1; }

        .breakdown {
            width: 100%;
            border-collapse: collapse;
        }

        .breakdown th {
            background: #eef6ff;
            color: #163c62;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            font-size: 10px;
        }

        .breakdown th,
        .breakdown td {
            border: 1px solid var(--line);
            padding: 8px;
        }

        .money { text-align: right; font-weight: 800; }
        .total-row td { background: #f7fbff; font-size: 14px; }
        .profit { color: #087443; }
        .loss { color: #b42318; }

        .notes {
            min-height: 56px;
            border: 1px solid var(--line);
            background: var(--soft);
            padding: 10px;
            white-space: pre-line;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 20px;
        }

        .signature-line {
            border-top: 1px solid #98a2b3;
            padding-top: 7px;
            color: var(--muted);
            font-size: 11px;
        }

        @media print {
            body { background: #fff; }
            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                border: 0;
                page-break-after: always;
            }
            @page { margin: 0.35in; }
        }
    </style>
</head>
<body>
    @forelse($items as $item)
        @php
            $baseCost = (float) ($item->msrp ?? 0);
            $partsCost = $item->signedPartsCost();
            $inventoryValue = $baseCost + $partsCost;
            $ourPrice = $baseCost * 0.7;
            $soldPrice = $item->sold_price !== null ? (float) $item->sold_price : null;
            $profit = $soldPrice !== null ? $soldPrice - $inventoryValue : null;
            $latestHistory = $item->statusHistories->sortByDesc('created_at')->first();
            $grade = $item->receiving_condition ? str_replace('-Grade', '', $item->receiving_condition) : 'N/A';
        @endphp

        <main class="sheet">
            <header class="header">
                <div>
                    <div class="company">Ben's Appliances</div>
                    <div class="subtitle">Inventory service sheet and cost breakdown</div>
                </div>
                <div class="doc-box">
                    <div class="doc-title">Unit Record</div>
                    <div class="doc-number">#{{ $item->id }}</div>
                    <div class="doc-title">{{ now()->format('M d, Y h:i A') }}</div>
                </div>
            </header>

            <div class="badge-row">
                <div class="badge status-badge"><strong>Status</strong>{{ $item->status ?: 'Triage' }}</div>
                <div class="badge grade-badge"><strong>Grade</strong>{{ $grade }}</div>
                <div class="badge"><strong>Truck</strong>{{ $item->truck?->name ?? 'Unassigned' }}</div>
                <div class="badge"><strong>Location</strong>{{ $item->location ?: 'Not set' }}</div>
            </div>

            <section class="grid">
                <div class="panel">
                    <div class="panel-title">Appliance Details</div>
                    <div class="panel-body">
                        <table class="details">
                            <tr><th>Brand</th><td>{{ $item->brand ?: '-' }}</td></tr>
                            <tr><th>Product</th><td>{{ $item->product_name ?: ($item->model?->product_name ?? '-') }}</td></tr>
                            <tr><th>Category</th><td>{{ $item->category?->name ?? '-' }}</td></tr>
                            <tr><th>Model #</th><td>{{ $item->model?->model_number ?? '-' }}</td></tr>
                            <tr><th>Serial #</th><td>{{ $item->serial_number ?: '-' }}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title">Receiving Summary</div>
                    <div class="panel-body">
                        <table class="details">
                            <tr><th>Receiving Date</th><td>{{ optional($item->created_at)->format('M d, Y') ?: '-' }}</td></tr>
                            <tr><th>Condition</th><td>{{ $item->receiving_condition ?: '-' }}</td></tr>
                            <tr><th>Current Status</th><td>{{ $item->status ?: 'Triage' }}</td></tr>
                            <tr><th>Last Update</th><td>{{ optional($latestHistory?->created_at ?? $item->updated_at)->format('M d, Y h:i A') ?: '-' }}</td></tr>
                            <tr><th>Updated By</th><td>{{ $latestHistory?->user?->name ?? $item->updater?->name ?? '-' }}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="panel wide">
                    <div class="panel-title">Complete Cost Breakdown</div>
                    <div class="panel-body">
                        <table class="breakdown">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Source / Part #</th>
                                    <th>Entered By</th>
                                    <th class="money">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Base appliance MSRP / cost basis</td>
                                    <td>{{ $item->model?->model_number ?? '-' }}</td>
                                    <td>Receiving</td>
                                    <td class="money">${{ number_format($baseCost, 2) }}</td>
                                </tr>
                                @forelse($item->parts as $part)
                                    <tr>
                                        <td>{{ $part->description }}</td>
                                        <td>{{ $part->part?->part_number ?? $part->part_number ?? '-' }}</td>
                                        <td>{{ $part->user?->name ?? '-' }}</td>
                                        <td class="money">${{ number_format((float) $part->cost, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>No parts attached</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td class="money">$0.00</td>
                                    </tr>
                                @endforelse
                                <tr class="total-row">
                                    <td colspan="3"><strong>Total Inventory Value</strong></td>
                                    <td class="money">${{ number_format($inventoryValue, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3"><strong>Suggested Our Price (70% MSRP)</strong></td>
                                    <td class="money">${{ number_format($ourPrice, 2) }}</td>
                                </tr>
                                @if($soldPrice !== null)
                                    <tr>
                                        <td colspan="3"><strong>Sold Price</strong> @if($item->sold_by) by {{ $item->sold_by }} @endif</td>
                                        <td class="money">${{ number_format($soldPrice, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"><strong>Profit / Loss</strong></td>
                                        <td class="money {{ $profit >= 0 ? 'profit' : 'loss' }}">${{ number_format($profit, 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel wide">
                    <div class="panel-title">Service Notes</div>
                    <div class="panel-body">
                        <div class="notes">{{ $latestHistory?->notes ?: 'No service notes recorded for this unit.' }}</div>
                    </div>
                </div>
            </section>

            <div class="signature-grid">
                <div class="signature-line">Prepared By</div>
                <div class="signature-line">Reviewed By</div>
            </div>
        </main>
    @empty
        <main class="sheet">
            <header class="header">
                <div>
                    <div class="company">Ben's Appliances</div>
                    <div class="subtitle">Inventory print request</div>
                </div>
            </header>
            <p>No inventory records matched this print request.</p>
        </main>
    @endforelse

    <script>
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
