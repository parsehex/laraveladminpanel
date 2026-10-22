<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Model as CatalogModel;
use App\Support\ApplianceStatusFilter;
use App\Support\FurnitureModelGroups;
use App\Support\PageSize;
use Illuminate\Http\Request;

class FurnitureModelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.view');
    }

    public function show(Request $request, CatalogModel $model, FurnitureModelGroups $groups)
    {
        $allUnits = FurnitureModelGroups::liveUnits()->where('truck_appliances.model_id', $model->id);

        abort_unless((clone $allUnits)->exists(), 404);

        $model->load('category');

        $selectedStatuses = ApplianceStatusFilter::normalize($request->input('status', []));

        $unitsQuery = (clone $allUnits)
            ->with(['truck', 'furnitureDetails'])
            ->orderByDesc('truck_appliances.truck_id')
            ->orderBy('truck_appliances.unit_label')
            ->orderBy('truck_appliances.id');
        ApplianceStatusFilter::apply($unitsQuery, $selectedStatuses);

        $shownUnitIds = (clone $unitsQuery)->pluck('truck_appliances.id');
        $units = PageSize::paginate($unitsQuery, $request);

        $firstUnit = (clone $allUnits)->orderBy('truck_appliances.id')->first();

        return view('admin.inventory.furniture-model', [
            'model' => $model,
            'productName' => $model->product_name ?: $firstUnit?->product_name,
            'brand' => $model->brand ?: $firstUnit?->brand,
            'msrp' => (float) $model->msrp ?: (float) (clone $allUnits)->max('truck_appliances.msrp'),
            'unitCount' => (clone $allUnits)->count(),
            'assembledCount' => (clone $allUnits)
                ->whereHas('furnitureDetails', fn ($details) => $details->whereNotNull('assembled_at'))
                ->count(),
            'statusCounts' => $groups->statusCounts(collect([$model->id]))->get($model->id, []),
            'truckBreakdown' => $groups->truckStatusCounts($model->id),
            'selectedStatuses' => $selectedStatuses->all(),
            'units' => $units,
            'shownUnitIds' => $shownUnitIds->all(),
        ]);
    }
}
