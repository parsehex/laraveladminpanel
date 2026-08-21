<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTruckRequest;
use App\Http\Requests\UpdateTruckRequest;
use App\Models\Category;
use App\Models\Model as ApplianceModel;
use App\Models\Truck;
use App\Support\DataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class TruckController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:trucks.view')->only(['index', 'show']);
        $this->middleware('permission:trucks.create')->only(['create', 'store']);
        $this->middleware('permission:trucks.edit')->only(['edit', 'update']);
        $this->middleware('permission:trucks.delete')->only(['destroy']);
        $this->authorizeResource(Truck::class, 'truck');
    }

    public function index(Request $request)
    {
        $dataTable = $this->trucksIndexDataTable();

        $query = Truck::query()
            ->with('creator', 'appliances')
            ->withSum('appliances as total_appliance_msrp', 'msrp')
            ->withSum(['appliances as revenue_to_date' => function ($query) {
                $query->where('status', 'Sold');
            }], 'sold_price');

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();

            $query->whereLike('name', '%'.$search.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $dataTable->applySorting($query, $request);

        $trucks = $query->paginate(12)->withQueryString();

        $trucks->getCollection()->transform(function ($truck) {
            $truck->appliance_statuses = $truck->appliances
                ->groupBy('status')
                ->map(fn ($items, $status) => [
                    'status' => $status,
                    'count' => $items->count(),
                ])
                ->values()
                ->toArray();

            return $truck;
        });

        return view('admin.trucks.index', [
            'trucks' => $trucks,
            'dataTable' => $dataTable,
            ...$dataTable->sortState($request),
        ]);
    }

    public function create()
    {
        return view('admin.trucks.create');
    }

    public function store(StoreTruckRequest $request)
    {
        $data = $request->validated();
        $data['shipping_cost'] = $data['shipping_cost'] ?? 0;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        Truck::create($data);

        return redirect()->route('admin.trucks.index')->with('success', __('Truck created successfully.'));
    }

    public function show(Request $request, Truck $truck)
    {
        $truck->load([
            'creator',
            'updater',
        ]);

        $dataTable = $this->truckAppliancesDataTable();
        $perPage = (int) $request->input('appliances_per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $appliancesQuery = $truck->appliances()->with(['category', 'model']);
        $dataTable->applySorting($appliancesQuery, $request);

        $appliances = $appliancesQuery
            ->paginate($perPage, ['*'], 'appliances_page')
            ->withQueryString();

        $allAppliances = $truck->appliances()
            ->with(['category', 'model'])
            ->orderBy('status')
            ->orderBy('id')
            ->get();
        $truck->setRelation('appliances', $allAppliances);

        $categoryIds = $allAppliances->pluck('category_id')->filter()->unique()->values();
        $modelIds = $allAppliances->pluck('model_id')->filter()->unique()->values();
        $categories = Category::query()->whereIn('id', $categoryIds)->orderBy('name')->get();
        $models = ApplianceModel::query()->whereIn('id', $modelIds)->orderBy('model_number')->get();

        return view('admin.trucks.show', [
            'truck' => $truck,
            'categories' => $categories,
            'models' => $models,
            'appliances' => $appliances,
            'perPage' => $perPage,
            'dataTable' => $dataTable,
            ...$dataTable->sortState($request),
        ]);
    }

    public function edit(Truck $truck)
    {
        return view('admin.trucks.edit', compact('truck'));
    }

    public function update(UpdateTruckRequest $request, Truck $truck)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $truck->update($data);

        return redirect()->route('admin.trucks.index')->with('success', __('Truck updated successfully.'));
    }

    public function destroy(Truck $truck)
    {
        $truck->delete();

        return redirect()->route('admin.trucks.index')->with('success', __('Truck deleted successfully.'));
    }

    private function trucksIndexDataTable(): DataTable
    {
        return new DataTable(
            storageKey: 'trucksIndexTableColumns',
            defaultSort: [['trucks.created_at', 'desc']],
            columns: [
                [
                    'key' => 'name',
                    'label' => 'Name',
                    'sort' => 'trucks.name',
                ],
                [
                    'key' => 'units',
                    'label' => 'Units',
                    'sortable' => false,
                ],
                [
                    'key' => 'cost',
                    'label' => 'Cost',
                    'align' => 'right',
                    'sort' => 'trucks.cost_of_truck',
                ],
                [
                    'key' => 'shipping',
                    'label' => 'Shipping',
                    'align' => 'right',
                    'sort' => 'trucks.shipping_cost',
                ],
                [
                    'key' => 'total_msrp',
                    'label' => 'Total MSRP',
                    'align' => 'right',
                    'sortable' => false,
                ],
                [
                    'key' => 'arrival',
                    'label' => 'Arrival',
                    'sort' => 'trucks.arrival_date',
                ],
                [
                    'key' => 'truck_status',
                    'label' => 'Truck Status',
                    'sort' => 'trucks.status',
                ],
                [
                    'key' => 'status_breakdown',
                    'label' => 'Status Breakdown',
                    'sortable' => false,
                ],
                [
                    'key' => 'revenue',
                    'label' => 'Revenue to Date',
                    'align' => 'right',
                    'sortable' => false,
                ],
                [
                    'key' => 'created_by',
                    'label' => 'Created by',
                    'truncate' => true,
                    'sort' => fn (Builder $query, string $direction) => $query
                        ->leftJoin('users', 'users.id', '=', 'trucks.created_by')
                        ->orderBy('users.name', $direction)
                        ->select('trucks.*'),
                ],
            ],
        );
    }

    private function truckAppliancesDataTable(): DataTable
    {
        return new DataTable(
            storageKey: 'truckAppliancesTableColumns',
            defaultSort: [
                ['truck_appliances.status', 'asc'],
                ['truck_appliances.id', 'asc'],
            ],
            columns: [
                [
                    'key' => 'category',
                    'label' => 'Category',
                    'truncate' => true,
                    'sort' => fn (Builder|Relation $query, string $direction) => $query
                        ->leftJoin('categories', 'categories.id', '=', 'truck_appliances.category_id')
                        ->orderBy('categories.name', $direction)
                        ->select('truck_appliances.*'),
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'sort' => fn (Builder|Relation $query, string $direction) => $query->orderByRaw(
                        "COALESCE(NULLIF(truck_appliances.status, ''), 'Triage') ".$direction
                    ),
                ],
                [
                    'key' => 'subcategory',
                    'label' => 'Sub-Category',
                    'truncate' => true,
                    'sort' => 'truck_appliances.subcategory',
                ],
                [
                    'key' => 'unit_label',
                    'label' => 'Unit Label',
                    'truncate' => true,
                    'sort' => 'truck_appliances.unit_label',
                ],
                [
                    'key' => 'model',
                    'label' => 'Model',
                    'sort' => fn (Builder|Relation $query, string $direction) => $query
                        ->leftJoin('models', 'models.id', '=', 'truck_appliances.model_id')
                        ->orderBy('models.model_number', $direction)
                        ->select('truck_appliances.*'),
                ],
                [
                    'key' => 'serial_number',
                    'label' => 'Serial #',
                    'sort' => 'truck_appliances.serial_number',
                ],
                [
                    'key' => 'brand',
                    'label' => 'Brand',
                    'truncate' => true,
                    'sort' => 'truck_appliances.brand',
                ],
                [
                    'key' => 'product_name',
                    'label' => 'Product Name',
                    'truncate' => true,
                    'sort' => 'truck_appliances.product_name',
                ],
                [
                    'key' => 'quantity',
                    'label' => 'Quantity',
                    'align' => 'right',
                    'sort' => 'truck_appliances.quantity',
                ],
                [
                    'key' => 'total_cost',
                    'label' => 'Total Cost',
                    'align' => 'right',
                    'sort' => fn (Builder|Relation $query, string $direction) => $query->orderByRaw(
                        '(COALESCE(truck_appliances.price, 0) + CASE WHEN COALESCE(truck_appliances.status, \'\') IN (\'Demanufacture\', \'Scrap\') THEN -COALESCE(truck_appliances.total_parts_cost, 0) ELSE COALESCE(truck_appliances.total_parts_cost, 0) END) '.$direction
                    ),
                ],
                [
                    'key' => 'msrp',
                    'label' => 'MSRP',
                    'align' => 'right',
                    'sort' => 'truck_appliances.msrp',
                ],
                [
                    'key' => 'fuel_type',
                    'label' => 'Fuel Type',
                    'truncate' => true,
                    'sort' => 'truck_appliances.fuel_type',
                ],
                [
                    'key' => 'receiving_condition',
                    'label' => 'Receiving Condition',
                    'truncate' => true,
                    'sort' => 'truck_appliances.receiving_condition',
                ],
                [
                    'key' => 'total_parts_cost',
                    'label' => 'Total Parts Cost',
                    'align' => 'right',
                    'sort' => 'truck_appliances.total_parts_cost',
                ],
            ],
        );
    }
}
