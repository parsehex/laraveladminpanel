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
        $normalDataTable = $this->normalSalesDataTable();

        $normalQuery = TruckAppliance::query()
            ->with(['model'])
            ->where('status', 'Sold');

        if ($search->isNotEmpty()) {
            $normalQuery->where(function (Builder $query) use ($search) {
                $query->whereLike('serial_number', '%'.$search.'%')
                    ->orWhereHas('model', fn (Builder $modelQuery) => $modelQuery->whereLike('model_number', '%'.$search.'%'));
            });
        }

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

        $normalRows = (clone $normalQuery)->get();
        $customRows = (clone $customQuery)->get();

        $normalSales = (float) $normalRows->sum(fn (TruckAppliance $item) => (float) ($item->sold_price ?? 0));
        $normalCost = (float) $normalRows->sum(fn (TruckAppliance $item) => $item->salesCost());
        $customSalesTotal = (float) $customRows->sum('sold_price');
        $customCost = (float) $customRows->sum('estimated_price');

        $soldItems = $limit === 'all'
            ? $normalQuery->paginate($normalQuery->count() ?: 1)->withQueryString()
            : $normalQuery->paginate($limit)->withQueryString();

        $customSales = $limit === 'all'
            ? $customQuery->paginate($customQuery->count() ?: 1)->withQueryString()
            : $customQuery->paginate($limit)->withQueryString();

        return view('admin.sales.index', [
            'view' => $view,
            'limit' => $limit,
            'soldItems' => $soldItems,
            'customSales' => $customSales,
            'dataTable' => $normalDataTable,
            ...$normalDataTable->sortState($request),
            'totalSales' => $view === 'normal' ? $normalSales : $customSalesTotal,
            'totalCost' => $view === 'normal' ? $normalCost : $customCost,
            'totalProfit' => $view === 'normal' ? $normalSales - $normalCost : $customSalesTotal - $customCost,
        ]);
    }

    private function normalSalesDataTable(): DataTable
    {
        return new DataTable(
            storageKey: 'normalSalesTableColumns',
            defaultSort: [
                ['truck_appliances.sold_at', 'desc'],
                ['truck_appliances.id', 'desc'],
            ],
            columns: [
                [
                    'key' => 'id',
                    'label' => 'ID',
                    'sort' => 'truck_appliances.id',
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
