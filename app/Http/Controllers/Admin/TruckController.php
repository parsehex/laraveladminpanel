<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTruckRequest;
use App\Http\Requests\UpdateTruckRequest;
use App\Models\Category;
use App\Models\Model as ApplianceModel;
use App\Models\Truck;
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
        $query = Truck::query()
            ->with('creator', 'appliances')
            ->withSum('appliances as total_appliance_msrp', 'msrp')
            ->withSum(['appliances as revenue_to_date' => function ($query) {
                $query->where('status', 'Sold');
            }], 'sold_price')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->trim();

            $query->whereLike('name', '%' . $search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $trucks = $query->paginate(12)->withQueryString();

        // Add unique appliance statuses
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
        

        return view('admin.trucks.index', compact('trucks'));
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

        $perPage = (int) $request->input('appliances_per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
        $appliances = $truck->appliances()
            ->with(['category', 'model'])
            ->orderBy('status') 
            ->orderBy('id')
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

        return view('admin.trucks.show', compact('truck', 'categories', 'models', 'appliances', 'perPage'));
    }

    public function edit(Truck $truck)
    {
        return view('admin.trucks.edit', compact('truck'));
    }

    public function update(UpdateTruckRequest $request, Truck $truck)
    {
        $data = $request->validated();
        $data['shipping_cost'] = $data['shipping_cost'] ?? 0;
        $data['updated_by'] = $request->user()->id;

        $truck->update($data);

        return redirect()->route('admin.trucks.index')->with('success', __('Truck updated successfully.'));
    }

    public function destroy(Truck $truck)
    {
        $truck->delete();

        return redirect()->route('admin.trucks.index')->with('success', __('Truck deleted successfully.'));
    }
}
