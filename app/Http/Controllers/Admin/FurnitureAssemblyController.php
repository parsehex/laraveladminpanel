<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TimeActivity;
use App\Http\Controllers\Controller;
use App\Models\TruckAppliance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FurnitureAssemblyController extends Controller
{
    public function clockIn(Request $request, TruckAppliance $appliance): RedirectResponse
    {
        $this->ensureFurniture($appliance);

        $appliance->timeEntries()->create([
            'activity' => TimeActivity::Assembly,
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'started_at' => now(),
        ]);

        return back()->with('success', __('Clocked in.'));
    }

    public function clockOut(Request $request, TruckAppliance $appliance): RedirectResponse
    {
        $this->ensureFurniture($appliance);

        $entry = $appliance->timeEntries()
            ->assembly()
            ->open()
            ->where('user_id', $request->user()->id)
            ->latest('started_at')
            ->first();

        if ($entry === null) {
            return back()->with('error', __('You are not clocked in on this piece.'));
        }

        $entry->update([
            'ended_at' => now(),
        ]);

        return back()->with('success', __('Clocked out.'));
    }

    public function markAssembled(Request $request, TruckAppliance $appliance): RedirectResponse
    {
        $this->ensureFurniture($appliance);

        $appliance->markAssembled($request->user());

        return back()->with('success', __('Marked assembled.'));
    }

    public function clearAssembled(TruckAppliance $appliance): RedirectResponse
    {
        $this->ensureFurniture($appliance);

        $appliance->furnitureDetails()->updateOrCreate([], [
            'assembled_at' => null,
            'assembled_by' => null,
            'assembled_by_name' => null,
        ]);

        return back()->with('success', __('Cleared assembled.'));
    }

    private function ensureFurniture(TruckAppliance $appliance): void
    {
        abort_unless($appliance->isFurniture(), 404);
    }
}
