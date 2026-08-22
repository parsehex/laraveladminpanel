<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Support\PageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:deliveries.view')->only('index');
        $this->middleware('permission:deliveries.create')->only('store');
        $this->middleware('permission:deliveries.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $search = $request->string('search')->trim();

        $query = Delivery::query()->latest();

        if ($search->isNotEmpty()) {
            $query->where(function (Builder $query) use ($search) {
                $query->whereLike('customer_name', '%'.$search.'%')
                    ->orWhereLike('customer_number', '%'.$search.'%');
            });
        }

        $deliveries = PageSize::paginate($query, $request);

        return view('admin.deliveries.index', [
            'deliveries' => $deliveries,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_number' => ['required', 'string', 'max:255'],
            'customer_address' => ['required', 'string'],
            'order_appliances' => ['required', 'string'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'delivery_timeframe' => ['nullable', 'string', 'max:255'],
            'delivery_type' => ['required', Rule::in(['Install', 'Drop Off'])],
            'haul_away' => ['nullable', 'boolean'],
            'collect_payment' => ['nullable', 'boolean'],
        ]);

        Delivery::create([
            ...$data,
            'delivery_fee' => $data['delivery_fee'] ?? 0,
            'haul_away' => $request->boolean('haul_away'),
            'collect_payment' => $request->boolean('collect_payment'),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Delivery added successfully.'));
    }

    public function destroy(Delivery $delivery)
    {
        $delivery->delete();

        return back()->with('success', __('Delivery deleted successfully.'));
    }
}
