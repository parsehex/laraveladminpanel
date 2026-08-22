<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSale;
use App\Models\TruckAppliance;
use App\Support\DataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesController extends Controller
{
    public const TRACKING_STATUSES = [
        'Ready',
        'Show Room',
        'Sold',
    ];

    public function __construct()
    {
        $this->middleware('permission:sales.view')->only('index');
        $this->middleware('permission:sales.create')->only('markSold');
        $this->middleware('permission:sales.edit')->only('updateSoldPrice');
    }

    public function index(Request $request)
    {
        $view = $request->get('view', 'normal') === 'custom' ? 'custom' : 'normal';
        $search = $request->string('search')->trim();
        $limit = $request->get('limit', 25);
        $limit = $limit === 'all' ? 'all' : max(1, (int) $limit);
        $selectedStatuses = $this->selectedTrackingStatuses($request);
        $normalDataTable = $this->normalSalesDataTable();

        $normalQuery = TruckAppliance::query()
            ->with(['model'])
            ->whereIn('status', self::TRACKING_STATUSES);

        if ($search->isNotEmpty()) {
            $normalQuery->where(function (Builder $query) use ($search) {
                $query->whereLike('serial_number', '%'.$search.'%')
                    ->orWhereLike('location', '%'.$search.'%')
                    ->orWhereLike('brand', '%'.$search.'%')
                    ->orWhereLike('product_name', '%'.$search.'%')
                    ->orWhereHas('model', fn (Builder $modelQuery) => $modelQuery->whereLike('model_number', '%'.$search.'%'));
            });
        }

        $statusCounts = (clone $normalQuery)
            ->reorder()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $soldTotalsQuery = (clone $normalQuery)->where('status', 'Sold');
        $normalQuery->whereIn('status', $selectedStatuses);

        $customQuery = CustomSale::query();

        if ($search->isNotEmpty()) {
            $customQuery->where(function (Builder $query) use ($search) {
                $query->whereLike('model_number', '%'.$search.'%')
                    ->orWhereLike('serial_number', '%'.$search.'%');
            });
        }

        $normalDataTable->applySorting($normalQuery, $request);

        $customSort = in_array($request->get('sort'), ['sold_price', 'sold_date'], true)
            ? $request->get('sort')
            : 'sold_date';
        $customDirection = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $customSortColumn = $customSort === 'sold_price' ? 'sold_price' : 'created_at';
        $customQuery->orderBy($customSortColumn, $customDirection)->orderByDesc('id');

        $normalSales = (float) (clone $soldTotalsQuery)->sum('sold_price');
        $normalCost = (float) (clone $soldTotalsQuery)->sum('price');
        $customRows = (clone $customQuery)->get();
        $customSalesTotal = (float) $customRows->sum('sold_price');
        $customCost = (float) $customRows->sum('estimated_price');

        $units = $limit === 'all'
            ? $normalQuery->paginate($normalQuery->count() ?: 1)->withQueryString()
            : $normalQuery->paginate($limit)->withQueryString();

        $customSales = $limit === 'all'
            ? $customQuery->paginate($customQuery->count() ?: 1)->withQueryString()
            : $customQuery->paginate($limit)->withQueryString();

        return view('admin.sales.index', [
            'view' => $view,
            'limit' => $limit,
            'units' => $units,
            'customSales' => $customSales,
            'dataTable' => $normalDataTable,
            'trackingStatuses' => self::TRACKING_STATUSES,
            'selectedStatuses' => $selectedStatuses,
            'statusCounts' => $statusCounts,
            ...$normalDataTable->sortState($request),
            'totalSales' => $view === 'normal' ? $normalSales : $customSalesTotal,
            'totalCost' => $view === 'normal' ? $normalCost : $customCost,
            'totalProfit' => $view === 'normal' ? $normalSales - $normalCost : $customSalesTotal - $customCost,
        ]);
    }

    /**
     * @return list<string>
     */
    private function selectedTrackingStatuses(Request $request): array
    {
        $statuses = collect($request->input('status', []))
            ->map(fn ($status) => trim((string) $status))
            ->filter(fn ($status) => in_array($status, self::TRACKING_STATUSES, true))
            ->unique()
            ->values()
            ->all();

        return $statuses ?: self::TRACKING_STATUSES;
    }

    private function trackingStatusOrderSql(): string
    {
        return "CASE COALESCE(truck_appliances.status, '') WHEN 'Show Room' THEN 0 WHEN 'Ready' THEN 1 WHEN 'Sold' THEN 2 ELSE 3 END";
    }

    private function normalSalesDataTable(): DataTable
    {
        return new DataTable(
            storageKey: 'normalSalesTableColumns',
            defaultSort: [
                [DB::raw('COALESCE(truck_appliances.sold_at, truck_appliances.updated_at)'), 'desc'],
                ['truck_appliances.id', 'desc'],
            ],
            columns: [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'sort' => 'truck_appliances.id',
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sort' => fn (Builder $query, string $direction) => $query
                        ->orderByRaw($this->trackingStatusOrderSql().' '.$direction),
                ],
                [
                    'key' => 'location',
                    'label' => 'Location',
                    'truncate' => true,
                    'sort' => 'truck_appliances.location',
                ],
                [
                    'key' => 'model',
                    'label' => 'Model',
                    'sort' => fn (Builder $query, string $direction) => $query
                        ->leftJoin('models', 'models.id', '=', 'truck_appliances.model_id')
                        ->orderBy('models.model_number', $direction)
                        ->select('truck_appliances.*'),
                ],
                [
                    'key' => 'serial_number',
                    'label' => 'Serial',
                    'sort' => 'truck_appliances.serial_number',
                ],
                [
                    'key' => 'sold_price',
                    'label' => 'Sold Price',
                    'align' => 'right',
                    'sort' => 'truck_appliances.sold_price',
                ],
                [
                    'key' => 'cost',
                    'label' => 'Cost',
                    'align' => 'right',
                    'sortable' => false,
                ],
                [
                    'key' => 'profit',
                    'label' => 'Profit',
                    'align' => 'right',
                    'sortable' => false,
                ],
                [
                    'key' => 'sold_by',
                    'label' => 'Sold By',
                    'truncate' => true,
                    'sort' => 'truck_appliances.sold_by',
                ],
                [
                    'key' => 'sold_date',
                    'label' => 'Sold Date',
                    'sort' => 'truck_appliances.sold_at',
                ],
            ],
        );
    }

    public function markSold(Request $request)
    {
        abort_unless($request->user()?->can('sales.create'), 403);

        $data = $request->validate([
            'sale_type' => ['required', Rule::in(['normal', 'custom'])],
            'serial_number' => ['required', 'string', 'max:255'],
            'sold_price' => ['required', 'numeric', 'min:0'],
            'model_number' => ['required_if:sale_type,custom', 'nullable', 'string', 'max:255'],
            'estimated_price' => ['required_if:sale_type,custom', 'nullable', 'numeric', 'min:0'],
        ]);

        if ($data['sale_type'] === 'custom') {
            CustomSale::create([
                'model_number' => strtoupper(preg_replace('/[^A-Z0-9-]/', '', $data['model_number'] ?? '')),
                'serial_number' => strtoupper(preg_replace('/[^A-Z0-9-]/', '', $data['serial_number'])),
                'sold_price' => $data['sold_price'],
                'estimated_price' => $data['estimated_price'],
                'sold_by' => $request->user()->name,
                'created_by' => $request->user()->id,
            ]);

            return back()->with('success', __('Custom sale saved successfully.'));
        }

        $serial = strtoupper(preg_replace('/[^A-Z0-9-]/', '', $data['serial_number']));
        $appliance = TruckAppliance::query()->where('serial_number', $serial)->first();

        if (! $appliance) {
            return back()->with('error', __('Item not found.'));
        }

        if ($appliance->status === 'Sold') {
            return back()->with('warning', __('Item already sold.'));
        }

        DB::transaction(function () use ($appliance, $data, $request) {
            $appliance->update([
                'status' => 'Sold',
                'location' => null,
                'sold_price' => $data['sold_price'],
                'sold_by' => $request->user()->name,
                'sold_at' => now(),
                'updated_by' => $request->user()->id,
            ]);

            $appliance->statusHistories()->create([
                'status' => 'Sold',
                'notes' => 'Marked sold from Sales Tracking.',
                'parts_ordered' => false,
                'user_id' => $request->user()->id,
            ]);
        });

        $cost = $appliance->salesCost();

        return back()->with('success', __('Item marked as sold. Profit: $:profit', [
            'profit' => number_format((float) $data['sold_price'] - $cost, 2),
        ]));
    }

    public function updateSoldPrice(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('sales.edit'), 403);
        abort_unless($appliance->status === 'Sold', 404);

        $data = $request->validate([
            'sold_price' => ['required', 'numeric', 'min:0'],
            'sold_by' => ['required', 'string', 'max:255'],
        ]);

        $appliance->update([
            'sold_price' => $data['sold_price'],
            'sold_by' => $data['sold_by'],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Sold price and sold by updated successfully.'));
    }
}
