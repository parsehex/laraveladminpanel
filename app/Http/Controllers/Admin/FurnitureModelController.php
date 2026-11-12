<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FurnitureQuickActionRequest;
use App\Models\Model as CatalogModel;
use App\Models\Truck;
use App\Support\ApplianceStatusFilter;
use App\Support\FurnitureModelGroups;
use App\Support\FurnitureQuickActions;
use App\Support\InventoryLocations;
use App\Support\PageSize;
use App\Support\TruckUnitLabels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FurnitureModelController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.view');
    }

    public function show(Request $request, CatalogModel $model, FurnitureModelGroups $groups, FurnitureQuickActions $actions)
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
            'statusCounts' => $groups->statusCounts([$model->id])->get($model->id, []),
            'truckBreakdown' => $groups->truckStatusCounts($model->id),
            'selectedStatuses' => $selectedStatuses->all(),
            'units' => $units,
            'shownUnitIds' => $shownUnitIds->all(),
            'targetStatuses' => FurnitureQuickActionRequest::targetStatuses(),
            'actionAvailability' => $actions->availability($model, FurnitureQuickActionRequest::targetStatuses()),
            'locations' => app(InventoryLocations::class)->suggestionLabels(),
            'trucks' => $this->trucksForAddAction($model),
        ]);
    }

    /**
     * Active trucks for Add units: ones that already have this model first (A→Z), then the rest (A→Z).
     *
     * @return list<array{id: int, name: string, next_unit_number: int}>
     */
    private function trucksForAddAction(CatalogModel $model): array
    {
        $modelTruckIds = FurnitureModelGroups::liveUnits()
            ->where('truck_appliances.model_id', $model->id)
            ->distinct()
            ->pluck('truck_appliances.truck_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $modelTruckLookup = array_flip($modelTruckIds);

        return Truck::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->sortBy(fn (Truck $truck): string => sprintf(
                '%d-%s',
                isset($modelTruckLookup[$truck->id]) ? 0 : 1,
                mb_strtolower((string) $truck->name),
            ))
            ->values()
            ->map(fn (Truck $truck): array => [
                'id' => $truck->id,
                'name' => $truck->name,
                'next_unit_number' => TruckUnitLabels::maxTrailingNumber($truck) + 1,
            ])
            ->all();
    }

    public function performAction(FurnitureQuickActionRequest $request, CatalogModel $model, FurnitureQuickActions $actions): RedirectResponse
    {
        $action = $request->quickAction();

        $result = $actions->perform(
            $model,
            $action,
            (int) $request->validated('count'),
            $request->checkedUnitIds(),
            $request->boolean('override'),
            $request->safe()->only(['status', 'sold_price', 'location', 'notes', 'truck_id']),
            $request->user(),
        );

        $message = __(':action :count :units: :labels.', [
            'action' => $action->pastTense(),
            'count' => $result['units']->count(),
            'units' => Str::plural('unit', $result['units']->count()),
            'labels' => FurnitureQuickActions::labels($result['units']),
        ]);

        if ($result['overridden_ids'] !== []) {
            $message .= ' '.__(':count used the status override.', ['count' => count($result['overridden_ids'])]);
        }

        return back()->with('success', $message);
    }
}
