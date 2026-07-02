<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSale;
use App\Models\TruckAppliance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sales.view')->only('index');
        $this->middleware('permission:sales.create')->only('markSold');
        $this->middleware('permission:sales.edit')->only('updateSoldPrice');
    }

    public function index(Request $request)
    {
        $view = $request->get('view', 'normal') === 'custom' ? 'custom' : 'normal';
        $search = $request->string('search')->trim();
        $limit = $request->get('limit', 25);
        $limit = $limit === 'all' ? 'all' : max(1, (int) $limit);
        $sort = in_array($request->get('sort'), ['sold_price', 'sold_date'], true)
            ? $request->get('sort')
            : 'sold_date';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $normalQuery = TruckAppliance::query()
            ->with(['model'])
            ->where('status', 'Sold');

        if ($search->isNotEmpty()) {
            $normalQuery->where(function (Builder $query) use ($search) {
                $query->whereLike('serial_number', '%'.$search.'%')
                    ->orWhereHas('model', fn (Builder $modelQuery) => $modelQuery->whereLike('model_number', '%'.$search.'%'));
            });
        }

        $customQuery = CustomSale::query();

        if ($search->isNotEmpty()) {
            $customQuery->where(function (Builder $query) use ($search) {
                $query->whereLike('model_number', '%'.$search.'%')
                    ->orWhereLike('serial_number', '%'.$search.'%');
            });
        }

        $normalSortColumn = $sort === 'sold_price' ? 'sold_price' : 'sold_at';
        $customSortColumn = $sort === 'sold_price' ? 'sold_price' : 'created_at';

        $normalQuery->orderBy($normalSortColumn, $direction)->orderByDesc('id');
        $customQuery->orderBy($customSortColumn, $direction)->orderByDesc('id');

        $normalRows = (clone $normalQuery)->get();
        $customRows = (clone $customQuery)->get();

        $normalSales = (float) $normalRows->sum(fn (TruckAppliance $item) => (float) ($item->sold_price ?? 0));
        $normalCost = (float) $normalRows->sum(fn (TruckAppliance $item) => $item->salesCost());
        $customSalesTotal = (float) $customRows->sum('sold_price');
        $customCost = (float) $customRows->sum('estimated_price');

        $soldItems = $limit === 'all'
            ? $normalQuery->paginate($normalQuery->count() ?: 1)->withQueryString()
            : $normalQuery->paginate($limit)->withQueryString();

        $customSales = $limit === 'all'
            ? $customQuery->paginate($customQuery->count() ?: 1)->withQueryString()
            : $customQuery->paginate($limit)->withQueryString();

        return view('admin.sales.index', [
            'view' => $view,
            'limit' => $limit,
            'soldItems' => $soldItems,
            'customSales' => $customSales,
            'sort' => $sort,
            'direction' => $direction,
            'totalSales' => $view === 'normal' ? $normalSales : $customSalesTotal,
            'totalCost' => $view === 'normal' ? $normalCost : $customCost,
            'totalProfit' => $view === 'normal' ? $normalSales - $normalCost : $customSalesTotal - $customCost,
        ]);
    }

    public function markSold(Request $request)
    {
        abort_unless($request->user()?->can('sales.create'), 403);

        $data = $request->validate([
            'sale_type' => ['required', Rule::in(['normal', 'custom'])],
            'serial_number' => ['required', 'string', 'max:255'],
            'sold_price' => ['required', 'numeric', 'min:0'],
            'model_number' => ['required_if:sale_type,custom', 'nullable', 'string', 'max:255'],
            'estimated_price' => ['required_if:sale_type,custom', 'nullable', 'numeric', 'min:0'],
        ]);

        if ($data['sale_type'] === 'custom') {
            CustomSale::create([
                'model_number' => strtoupper(preg_replace('/[^A-Z0-9-]/', '', $data['model_number'] ?? '')),
                'serial_number' => strtoupper(preg_replace('/[^A-Z0-9-]/', '', $data['serial_number'])),
                'sold_price' => $data['sold_price'],
                'estimated_price' => $data['estimated_price'],
                'sold_by' => $request->user()->name,
                'created_by' => $request->user()->id,
            ]);

            return back()->with('success', __('Custom sale saved successfully.'));
        }

        $serial = strtoupper(preg_replace('/[^A-Z0-9-]/', '', $data['serial_number']));
        $appliance = TruckAppliance::query()->where('serial_number', $serial)->first();

        if (! $appliance) {
            return back()->with('error', __('Item not found.'));
        }

        if ($appliance->status === 'Sold') {
            return back()->with('warning', __('Item already sold.'));
        }

        DB::transaction(function () use ($appliance, $data, $request) {
            $appliance->update([
                'status' => 'Sold',
                'sold_price' => $data['sold_price'],
                'sold_by' => $request->user()->name,
                'sold_at' => now(),
                'updated_by' => $request->user()->id,
            ]);

            $appliance->statusHistories()->create([
                'status' => 'Sold',
                'notes' => 'Marked sold from Sales Tracking.',
                'parts_ordered' => false,
                'user_id' => $request->user()->id,
            ]);
        });

        $cost = $appliance->salesCost();

        return back()->with('success', __('Item marked as sold. Profit: $:profit', [
            'profit' => number_format((float) $data['sold_price'] - $cost, 2),
        ]));
    }

    public function updateSoldPrice(Request $request, TruckAppliance $appliance)
    {
        abort_unless($request->user()?->can('sales.edit'), 403);
        abort_unless($appliance->status === 'Sold', 404);

        $data = $request->validate([
            'sold_price' => ['required', 'numeric', 'min:0'],
            'sold_by' => ['required', 'string', 'max:255'],
        ]);

        $appliance->update([
            'sold_price' => $data['sold_price'],
            'sold_by' => $data['sold_by'],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Sold price and sold by updated successfully.'));
    }
}
