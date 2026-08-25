<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\TruckAppliance;
use App\Models\UserAction;
use App\Notifications\DeliveryCreatedNotification;
use App\Support\ModuleNotifier;
use App\Support\PageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public const NOTIFICATION_MODULE = 'deliveries';

    public function __construct()
    {
        $this->middleware('permission:deliveries.view')->only(['index', 'searchAppliances']);
        $this->middleware('permission:deliveries.create')->only('store');
        $this->middleware('permission:deliveries.complete')->only(['complete', 'restore']);
    }

    public function index(Request $request)
    {
        $search = $request->string('search')->trim();
        $status = $request->string('status')->toString() === 'completed' ? 'completed' : 'active';

        $query = Delivery::query()
            ->withCount('appliances')
            ->latest();

        if ($status === 'completed') {
            $query->whereNotNull('completed_at');
        } else {
            $query->whereNull('completed_at');
        }

        if ($search->isNotEmpty()) {
            $query->where(function (Builder $query) use ($search) {
                $query->whereLike('customer_name', '%'.$search.'%')
                    ->orWhereLike('customer_number', '%'.$search.'%')
                    ->orWhereLike('order_appliances', '%'.$search.'%');
            });
        }

        $deliveries = PageSize::paginate($query, $request);

        $selectedAppliances = collect();

        $oldApplianceIds = collect(old('appliance_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($oldApplianceIds !== []) {
            $selectedAppliances = TruckAppliance::query()
                ->with('model:id,model_number')
                ->whereIn('id', $oldApplianceIds)
                ->get()
                ->keyBy('id');
        }

        return view('admin.deliveries.index', [
            'deliveries' => $deliveries,
            'listStatus' => $status,
            'selectedAppliances' => $selectedAppliances,
            'oldApplianceIds' => $oldApplianceIds,
        ]);
    }

    public function searchAppliances(Request $request)
    {
        $search = $request->string('q')->trim()->toString();

        $query = TruckAppliance::query()
            ->with('model:id,model_number')
            ->whereIn('status', Delivery::PICKER_STATUSES)
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->whereLike('brand', '%'.$search.'%')
                    ->orWhereLike('product_name', '%'.$search.'%')
                    ->orWhereLike('serial_number', '%'.$search.'%')
                    ->orWhereLike('unit_label', '%'.$search.'%')
                    ->orWhereHas('model', function (Builder $modelQuery) use ($search) {
                        $modelQuery->whereLike('model_number', '%'.$search.'%');
                    });
            });
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (TruckAppliance $appliance) => [
                'id' => $appliance->id,
                'text' => Delivery::applianceLabel($appliance),
            ])->values(),
            'next_page' => $paginator->hasMorePages(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_number' => ['required', 'string', 'max:255'],
            'customer_address' => ['required', 'string'],
            'appliance_ids' => ['required', 'array', 'min:1'],
            'appliance_ids.*' => ['integer', 'distinct', 'exists:truck_appliances,id'],
            'notes' => ['nullable', 'string'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'delivery_timeframe' => ['nullable', 'string', 'max:255'],
            'delivery_type' => ['required', Rule::in(['Install', 'Drop Off'])],
            'haul_away' => ['nullable', 'boolean'],
            'collect_payment' => ['nullable', 'boolean'],
        ]);

        $appliances = TruckAppliance::query()
            ->with('model:id,model_number')
            ->whereIn('id', $data['appliance_ids'])
            ->whereIn('status', Delivery::PICKER_STATUSES)
            ->get();

        if ($appliances->count() !== count($data['appliance_ids'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'appliance_ids' => __('One or more selected units are unavailable for delivery.'),
                ]);
        }

        $delivery = DB::transaction(function () use ($request, $data, $appliances) {
            $delivery = Delivery::create([
                'customer_name' => $data['customer_name'],
                'customer_number' => $data['customer_number'],
                'customer_address' => $data['customer_address'],
                'order_appliances' => Delivery::snapshotFromAppliances($appliances),
                'notes' => $data['notes'] ?? null,
                'delivery_fee' => $data['delivery_fee'] ?? 0,
                'delivery_timeframe' => $data['delivery_timeframe'] ?? null,
                'delivery_type' => $data['delivery_type'],
                'haul_away' => $request->boolean('haul_away'),
                'collect_payment' => $request->boolean('collect_payment'),
                'created_by' => $request->user()->id,
            ]);

            $delivery->appliances()->sync($appliances->pluck('id')->all());

            return $delivery;
        });

        ModuleNotifier::notify(
            self::NOTIFICATION_MODULE,
            new DeliveryCreatedNotification($delivery),
            (int) $request->user()->id
        );

        UserAction::log('create_delivery', null, [
            'delivery_id' => $delivery->id,
            'customer_name' => $delivery->customer_name,
            'customer_number' => $delivery->customer_number,
            'delivery_type' => $delivery->delivery_type,
            'delivery_fee' => (float) $delivery->delivery_fee,
            'delivery_timeframe' => $delivery->delivery_timeframe,
            'haul_away' => $delivery->haul_away,
            'collect_payment' => $delivery->collect_payment,
            'appliance_ids' => $appliances->pluck('id')->values()->all(),
            'appliance_count' => $appliances->count(),
        ]);

        return back()->with('success', __('Delivery added successfully.'));
    }

    public function complete(Delivery $delivery)
    {
        if ($delivery->completed_at === null) {
            $delivery->update(['completed_at' => now()]);

            UserAction::log('complete_delivery', null, [
                'delivery_id' => $delivery->id,
                'customer_name' => $delivery->customer_name,
                'delivery_type' => $delivery->delivery_type,
            ]);
        }

        return back()->with('success', __('Delivery marked complete.'));
    }

    public function restore(Delivery $delivery)
    {
        if ($delivery->completed_at !== null) {
            $delivery->update(['completed_at' => null]);

            UserAction::log('restore_delivery', null, [
                'delivery_id' => $delivery->id,
                'customer_name' => $delivery->customer_name,
                'delivery_type' => $delivery->delivery_type,
            ]);
        }

        return back()->with('success', __('Delivery restored to active.'));
    }
}
