<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RenameInventoryLocationRequest;
use App\Models\TruckAppliance;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory-locations.manage');
    }

    public function index(): View
    {
        $locations = TruckAppliance::query()
            ->whereNotNull('location')
            ->where('location', '<>', '')
            ->select('location')
            ->selectRaw('COUNT(*) as item_count')
            ->groupBy('location')
            ->orderBy('location')
            ->get();

        return view('admin.inventory-locations.index', [
            'locations' => $locations,
        ]);
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

        $targetExisted = TruckAppliance::query()->where('location', $to)->exists();

        $updated = TruckAppliance::query()
            ->where('location', $from)
            ->update(['location' => $to]);

        if ($updated === 0) {
            return redirect()
                ->route('admin.inventory-locations.index')
                ->with('error', __('No items used that location.'));
        }

        $message = $targetExisted
            ? __(':count item(s) merged into :to.', ['count' => $updated, 'to' => $to])
            : __(':count item(s) renamed to :to.', ['count' => $updated, 'to' => $to]);

        return redirect()
            ->route('admin.inventory-locations.index')
            ->with('success', $message);
    }
}
