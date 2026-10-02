<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApplyInventoryStatusAutoLocationRequest;
use App\Http\Requests\Admin\ArchiveInventoryStatusRequest;
use App\Http\Requests\Admin\StoreInventoryStatusRequest;
use App\Http\Requests\Admin\UpdateInventoryStatusRequest;
use App\Models\InventoryStatus;
use App\Models\TruckAppliance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory-statuses.manage');
    }

    public function index(): View
    {
        $itemCounts = TruckAppliance::query()
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->whereNotNull('status')
            ->where('status', '<>', '')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $itemsNeedingLocation = DB::table('truck_appliances as appliances')
            ->join('inventory_statuses as statuses', 'statuses.name', '=', 'appliances.status')
            ->whereNotNull('statuses.auto_location')
            ->where('statuses.auto_location', '<>', '')
            ->where(function ($query) {
                $query->whereNull('appliances.location')
                    ->orWhere('appliances.location', '')
                    ->orWhereRaw('LOWER(TRIM(appliances.location)) <> LOWER(TRIM(statuses.auto_location))');
            })
            ->groupBy('statuses.name')
            ->selectRaw('statuses.name as status_name, COUNT(*) as aggregate')
            ->get()
            ->pluck('aggregate', 'status_name');

        $statuses = InventoryStatus::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (InventoryStatus $status) use ($itemCounts, $itemsNeedingLocation) {
                $status->item_count = (int) ($itemCounts[$status->name] ?? 0);
                $status->items_needing_location = (int) ($itemsNeedingLocation[$status->name] ?? 0);

                return $status;
            });

        return view('admin.inventory-statuses.index', [
            'statuses' => $statuses,
        ]);
    }

    public function store(StoreInventoryStatusRequest $request): RedirectResponse
    {
        InventoryStatus::query()->create([
            'name' => $request->validated('name'),
            'auto_location' => $request->validated('auto_location'),
            'is_system' => false,
            'sort_order' => InventoryStatus::nextSortOrder(),
        ]);

        return redirect()
            ->route('admin.inventory-statuses.index')
            ->with('success', __('Status created successfully.'));
    }

    public function update(UpdateInventoryStatusRequest $request, InventoryStatus $inventoryStatus): RedirectResponse
    {
        $inventoryStatus->update([
            'auto_location' => $request->validated('auto_location'),
        ]);

        return redirect()
            ->route('admin.inventory-statuses.index')
            ->with('success', __('Status updated successfully.'));
    }

    public function archive(ArchiveInventoryStatusRequest $request, InventoryStatus $inventoryStatus): RedirectResponse
    {
        $inventoryStatus->update([
            'archived_at' => now(),
        ]);

        return redirect()
            ->route('admin.inventory-statuses.index')
            ->with('success', __('Status archived successfully.'));
    }

    public function applyAutoLocation(
        ApplyInventoryStatusAutoLocationRequest $request,
        InventoryStatus $inventoryStatus,
    ): RedirectResponse {
        $location = $inventoryStatus->auto_location;
        $updated = 0;

        DB::transaction(function () use ($inventoryStatus, $location, $request, &$updated): void {
            $updated = TruckAppliance::query()
                ->where('status', $inventoryStatus->name)
                ->where(function ($query) use ($location) {
                    $query->whereNull('location')
                        ->orWhere('location', '')
                        ->orWhereRaw('LOWER(TRIM(location)) <> ?', [strtolower(trim((string) $location))]);
                })
                ->update([
                    'location' => $location,
                    'updated_by' => $request->user()->id,
                    'updated_at' => now(),
                ]);
        });

        return redirect()
            ->route('admin.inventory-statuses.index')
            ->with('success', __(':count item(s) updated to :location.', [
                'count' => $updated,
                'location' => $location,
            ]));
    }
}
