<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTruckApplianceRequest;
use App\Http\Requests\UpdateTruckApplianceRequest;
use App\Models\Truck;
use App\Models\TruckAppliance;
use Illuminate\Http\Request;

class TruckApplianceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:appliance.create')->only('store');
        $this->middleware('permission:appliance.edit')->only('update');
        $this->middleware('permission:appliance.delete')->only('destroy');
    }

    public function store(StoreTruckApplianceRequest $request, Truck $truck)
    {
        $data = $request->validated();
        abort_unless((int) $data['truck_id'] === $truck->id, 403);

        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $truck->appliances()->create($data);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance added successfully.'));
    }

    public function update(UpdateTruckApplianceRequest $request, Truck $truck, TruckAppliance $appliance)
    {
        abort_unless($appliance->truck_id === $truck->id, 404);

        $data = $request->validated();
        abort_unless((int) $data['truck_id'] === $truck->id, 403);

        $data['updated_by'] = $request->user()->id;

        $appliance->update($data);

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance updated successfully.'));
    }

    public function destroy(Request $request, Truck $truck, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('appliance.delete'), 403);
        abort_unless($appliance->truck_id === $truck->id, 404);

        $appliance->delete();

        return redirect()->route('admin.trucks.show', $truck)->with('success', __('Appliance removed successfully.'));
    }
}
