<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RenameInventoryLocationRequest;
use App\Http\Requests\Admin\StoreInventoryLocationRequest;
use App\Models\InventoryLocation;
use App\Models\TruckAppliance;
use App\Support\InventoryLocations;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory-locations.manage');
    }

    public function index(InventoryLocations $locations): View
    {
        return view('admin.inventory-locations.index', [
            'catalog' => $locations->catalogWithCounts(),
            'uncatalogued' => $locations->uncataloguedInUse(),
        ]);
    }

    public function store(StoreInventoryLocationRequest $request): RedirectResponse
    {
        InventoryLocation::query()->create([
            'name' => $request->validated('name'),
        ]);

        return redirect()
            ->route('admin.inventory-locations.index')
            ->with('success', __('Location added.'));
    }

    public function rename(RenameInventoryLocationRequest $request): RedirectResponse
    {
        $from = $request->validated('from');
        $to = $request->validated('to');

        if ($from === $to) {
            return redirect()
                ->route('admin.inventory-locations.index')
                ->with('error', __('The new location name must be different.'));
        }

        $targetExistedOnItems = TruckAppliance::query()->where('location', $to)->exists();
        $catalogFrom = InventoryLocation::query()->where('name', $from)->first();
        $catalogTo = InventoryLocation::query()->where('name', $to)->first();

        $updated = TruckAppliance::query()
            ->where('location', $from)
            ->update(['location' => $to]);

        if ($catalogFrom) {
            if ($catalogTo) {
                $catalogFrom->delete();
            } else {
                $catalogFrom->update(['name' => $to]);
            }
        }

        if ($updated === 0 && ! $catalogFrom) {
            return redirect()
                ->route('admin.inventory-locations.index')
                ->with('error', __('No items used that location.'));
        }

        if ($catalogFrom && $updated === 0) {
            $message = __('Location renamed to :to.', ['to' => $to]);
        } elseif ($targetExistedOnItems || $catalogTo) {
            $message = __(':count item(s) merged into :to.', ['count' => $updated, 'to' => $to]);
        } else {
            $message = __(':count item(s) renamed to :to.', ['count' => $updated, 'to' => $to]);
        }

        return redirect()
            ->route('admin.inventory-locations.index')
            ->with('success', $message);
    }
}
