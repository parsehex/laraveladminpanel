<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTruckRequest;
use App\Http\Requests\UpdateTruckRequest;
use App\Imports\StagedCsvImport;
use App\Imports\TruckCsvImport;
use App\Models\Category;
use App\Models\Model as ApplianceModel;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\UserAction;
use App\Support\DataTable;
use App\Support\PageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TruckController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:trucks.view')->only(['index', 'show']);
        $this->middleware('permission:trucks.create')->only([
            'create',
            'store',
            'importPreview',
            'importReview',
            'importConfirm',
            'importCancel',
        ]);
        $this->middleware('permission:trucks.edit')->only(['edit', 'update']);
        $this->middleware('permission:trucks.delete')->only(['destroy']);
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

        $trucks = PageSize::paginate($query, $request);

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

        $truck = Truck::create($data);

        UserAction::log('add_truck', null, [
            'truck_id' => $truck->id,
            'name' => $truck->name,
        ]);

        return redirect()->route('admin.trucks.index')->with('success', __('Truck created successfully.'));
    }

    public function importPreview(Request $request)
    {
        abort_unless($request->user()?->can('trucks.create'), 403);

        $data = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $this->stagedCsvImport()->stage($request, $data['csv_file']);

        return redirect()->route('admin.trucks.import.review');
    }

    public function importReview(Request $request, TruckCsvImport $importer)
    {
        abort_unless($request->user()?->can('trucks.create'), 403);

        try {
            $staged = $this->stagedCsvImport()->require($request);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.trucks.index')
                ->with('error', $exception->errors()['csv_file'][0] ?? __('Upload the CSV again.'));
        }

        $preview = $importer->preview($this->stagedCsvImport()->absolutePath($staged));

        return view('admin.shared.csv-import-review', [
            'title' => 'Review Truck Import',
            'subtitle' => null,
            'preview' => $preview,
            'confirmRoute' => route('admin.trucks.import.confirm'),
            'cancelRoute' => route('admin.trucks.import.cancel'),
            'backRoute' => route('admin.trucks.index'),
            'backLabel' => 'Back to trucks',
            'matchHelp' => 'Review the changes below. Matching uses truck name. Confirm only when there are no errors.',
        ]);
    }

    public function importConfirm(Request $request, TruckCsvImport $importer)
    {
        abort_unless($request->user()?->can('trucks.create'), 403);

        $staging = $this->stagedCsvImport();

        try {
            $staged = $staging->require($request);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.trucks.index')
                ->with('error', $exception->errors()['csv_file'][0] ?? __('Upload the CSV again.'));
        }

        $absolutePath = $staging->absolutePath($staged);

        try {
            $preview = $importer->preview($absolutePath);

            if (! $preview->canConfirm()) {
                return redirect()
                    ->route('admin.trucks.import.review')
                    ->with('error', __('Fix the CSV errors before confirming the import.'));
            }

            $result = $importer->commit($absolutePath, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.trucks.import.review')
                ->withErrors($exception->errors());
        }

        $staging->discard($request);

        return redirect()
            ->route('admin.trucks.index')
            ->with('success', __("Import successful! Added {$result->imported}, updated {$result->updated}."));
    }

    public function importCancel(Request $request)
    {
        abort_unless($request->user()?->can('trucks.create'), 403);

        $this->stagedCsvImport()->discard($request);

        return redirect()->route('admin.trucks.index')->with('success', __('Import cancelled.'));
    }

    private function stagedCsvImport(): StagedCsvImport
    {
        return new StagedCsvImport('truck_csv_import');
    }

    public function show(Request $request, Truck $truck)
    {
        $truck->load([
            'creator',
            'updater',
        ]);

        $dataTable = $this->truckAppliancesDataTable();

        $appliancesQuery = $truck->appliances()
            ->with(['category', 'model'])
            ->withSum('parts as parts_sum_cost', 'cost');
        $dataTable->applySorting($appliancesQuery, $request);

        $appliances = PageSize::paginate(
            $appliancesQuery,
            $request,
            name: 'appliances_per_page',
            pageName: 'appliances_page',
        );

        $allAppliances = $truck->appliances()
            ->with(['category', 'model'])
            ->withSum('parts as parts_sum_cost', 'cost')
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

        UserAction::log('edit_truck', null, [
            'truck_id' => $truck->id,
            'name' => $truck->name,
        ]);

        return redirect()->route('admin.trucks.index')->with('success', __('Truck updated successfully.'));
    }

    public function destroy(Truck $truck)
    {
        UserAction::log('delete_truck', null, [
            'truck_id' => $truck->id,
            'name' => $truck->name,
        ]);

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
                    'sort' => fn (Builder $query, string $direction) => $query->orderByRaw(
                        '(trucks.cost_of_truck + trucks.shipping_cost) '.$direction
                    ),
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
                        TruckAppliance::totalCostSql().' '.$direction
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
            ],
        );
    }
}
