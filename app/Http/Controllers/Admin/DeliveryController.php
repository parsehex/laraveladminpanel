<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
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
        $limit = $request->get('limit', 25);
        $limit = $limit === 'all' ? 'all' : max(1, (int) $limit);

        $query = Delivery::query()->latest();

        if ($search->isNotEmpty()) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('customer_number', 'like', '%'.$search.'%');
            });
        }

        $totalRecords = (clone $query)->count();
        $deliveries = $limit === 'all'
            ? $query->paginate($totalRecords ?: 1)->withQueryString()
            : $query->paginate($limit)->withQueryString();

        return view('admin.deliveries.index', [
            'deliveries' => $deliveries,
            'limit' => $limit,
            'totalRecords' => $totalRecords,
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
