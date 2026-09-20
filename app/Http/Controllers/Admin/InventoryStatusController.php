<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArchiveInventoryStatusRequest;
use App\Http\Requests\Admin\StoreInventoryStatusRequest;
use App\Http\Requests\Admin\UpdateInventoryStatusRequest;
use App\Models\InventoryStatus;
use App\Models\TruckAppliance;
use Illuminate\Http\RedirectResponse;
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

        $statuses = InventoryStatus::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (InventoryStatus $status) use ($itemCounts) {
                $status->item_count = (int) ($itemCounts[$status->name] ?? 0);

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
}
