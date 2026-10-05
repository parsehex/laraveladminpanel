<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSale;
use App\Models\InventoryStatusHistory;
use App\Models\Suggestion;
use App\Models\TruckAppliance;
use App\Models\User;
use App\Models\UserAction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $periodLabel] = $this->resolvePeriod($request);

        $unitsAdded = TruckAppliance::query()->whereBetween('created_at', [$from, $to]);

        $soldInPeriod = TruckAppliance::query()
            ->where('status', 'Sold')
            ->whereRaw('COALESCE(sold_at, updated_at) BETWEEN ? AND ?', [$from, $to]);

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_units' => (clone $unitsAdded)->count(),
            'inventory_value' => (clone $unitsAdded)
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '')
                        ->orWhereNotIn('status', ['Sold', 'Show Room']);
                })
                ->selectRaw('SUM('.TruckAppliance::totalCostSql().') as value')
                ->value('value') ?: 0,
            'sold_units' => (clone $soldInPeriod)->count(),
            'sales_total' => (float) (clone $soldInPeriod)->sum('sold_price')
                + (float) CustomSale::query()->whereBetween('created_at', [$from, $to])->sum('sold_price'),
        ];

        $activityRows = $this->activityRows($from, $to);

        $holdingForParts = TruckAppliance::query()
            ->with(['truck', 'model', 'category', 'statusHistories.user'])
            ->where('status', 'Holding for parts')
            ->latest('updated_at')
            ->take(25)
            ->get();

        $holding = TruckAppliance::query()
            ->appliances()
            ->with(['truck', 'model', 'category', 'statusHistories.user'])
            ->where('status', 'Holding')
            ->latest('updated_at')
            ->take(25)
            ->get();

        $suggestionStatus = $request->get('suggestion_status', 'pending');
        if (! in_array($suggestionStatus, ['pending', 'completed', 'all'], true)) {
            $suggestionStatus = 'pending';
        }

        $suggestions = Suggestion::query()
            ->with(['user', 'completedBy'])
            ->when($suggestionStatus !== 'all', fn ($query) => $query->where('status', $suggestionStatus))
            ->latest()
            ->paginate(10, ['*'], 'suggestions_page')
            ->withQueryString();

        return view('admin.dashboard', [
            'stats' => $stats,
            'activityRows' => $activityRows,
            'holdingForParts' => $holdingForParts,
            'holding' => $holding,
            'suggestions' => $suggestions,
            'suggestionStatus' => $suggestionStatus,
            'period' => $request->get('period', 'weekly'),
            'periodLabel' => $periodLabel,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function executive(Request $request)
    {
        [$from, $to, $periodLabel] = $this->resolvePeriod($request);
        $productionRows = $this->productionRows($from, $to);
        $productionTotals = [
            'total_units' => (int) $productionRows->sum('total_units'),
            'total_msrp' => (float) $productionRows->sum('total_msrp'),
        ];

        return view('admin.executive-dashboard', [
            'productionRows' => $productionRows,
            'productionTotals' => $productionTotals,
            'period' => $request->get('period', 'weekly'),
            'periodLabel' => $periodLabel,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function storeSuggestionResponse(Request $request, Suggestion $suggestion)
    {
        $data = $request->validate([
            'response' => ['required', 'string', 'max:1000'],
        ]);

        $responses = $suggestion->responses ?? [];
        $responses[] = [
            'user' => $request->user()->name,
            'message' => $data['response'],
            'created_at' => now()->toDateTimeString(),
        ];

        $suggestion->update(['responses' => $responses]);

        return back()->with('success', __('Response added successfully.'));
    }

    public function completeSuggestion(Request $request, Suggestion $suggestion)
    {
        abort_unless($request->user()?->can('suggestions.complete'), 403);

        $suggestion->update([
            'status' => 'completed',
            'completed_by' => $request->user()->id,
            'completed_at' => now(),
        ]);

        return back()->with('success', __('Suggestion marked complete.'));
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->get('period', 'weekly');
        $now = now();

        if ($period === 'custom' && $request->filled(['from', 'to'])) {
            $from = Carbon::parse($request->get('from'))->startOfDay();
            $to = Carbon::parse($request->get('to'))->endOfDay();

            return [$from, $to, $from->format('M d, Y').' - '.$to->format('M d, Y')];
        }

        return match ($period) {
            'daily' => [$now->copy()->subDay()->startOfDay(), $now->copy()->endOfDay(), 'Last 1 day'],
            'monthly' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay(), 'Last 30 days'],
            'yearly' => [$now->copy()->subDays(365)->startOfDay(), $now->copy()->endOfDay(), 'Last 365 days'],
            'all' => [Carbon::create(1970, 1, 1)->startOfDay(), $now->copy()->endOfDay(), 'All time'],
            default => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay(), 'Last 7 days'],
        };
    }

    /**
     * Staff activity breakdown from user_actions for the selected period.
     * Matches the legacy dashboard.php aggregates.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function activityRows(Carbon $from, Carbon $to): Collection
    {
        $trackedActions = [
            'add_truck',
            'delete_truck',
            'add_appliance',
            'create_appliance',
            'delete_appliance',
            'test_unit',
            'deman_unit',
            'repair_unit',
            'showroom_sent',
            'mark_sold',
        ];

        return UserAction::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('username')
            ->selectRaw("COUNT(CASE WHEN action_type = 'add_truck' THEN 1 END) AS trucks_added")
            ->selectRaw("COUNT(CASE WHEN action_type = 'delete_truck' THEN 1 END) AS trucks_deleted")
            ->selectRaw("COUNT(CASE WHEN action_type IN ('add_appliance', 'create_appliance') THEN 1 END) AS units_added")
            ->selectRaw("COUNT(CASE WHEN action_type = 'delete_appliance' THEN 1 END) AS units_deleted")
            ->selectRaw("COALESCE(SUM(CASE WHEN action_type IN ('add_appliance', 'create_appliance') THEN (SELECT msrp FROM truck_appliances WHERE truck_appliances.id = user_actions.item_id) ELSE 0 END), 0) AS total_msrp_added")
            ->selectRaw("COUNT(DISTINCT CASE WHEN action_type = 'test_unit' THEN item_id END) AS units_tested")
            ->selectRaw("COUNT(DISTINCT CASE WHEN action_type = 'deman_unit' THEN item_id END) AS demanufactured")
            ->selectRaw("COUNT(DISTINCT CASE WHEN action_type IN ('repair_unit', 'test_unit') THEN item_id END) AS repaired")
            ->selectRaw("COUNT(DISTINCT CASE WHEN action_type = 'showroom_sent' THEN item_id END) AS showroom_sent")
            ->selectRaw("COUNT(CASE WHEN action_type = 'mark_sold' THEN 1 END) AS sales_marked")
            ->groupBy('username')
            ->havingRaw(
                'COUNT(CASE WHEN action_type IN ('.implode(', ', array_fill(0, count($trackedActions), '?')).') THEN 1 END) > 0',
                $trackedActions
            )
            ->orderBy('username')
            ->get()
            ->map(function ($row): array {
                return [
                    'username' => $row->username ?: 'Unknown',
                    'trucks_added' => (int) $row->trucks_added,
                    'trucks_deleted' => (int) $row->trucks_deleted,
                    'units_added' => (int) $row->units_added,
                    'units_deleted' => (int) $row->units_deleted,
                    'total_msrp_added' => (float) $row->total_msrp_added,
                    'units_tested' => (int) $row->units_tested,
                    'demanufactured' => (int) $row->demanufactured,
                    'repaired' => (int) $row->repaired,
                    'showroom_sent' => (int) $row->showroom_sent,
                    'sales_marked' => (int) $row->sales_marked,
                ];
            });
    }

    private function productionRows(Carbon $from, Carbon $to)
    {
        return InventoryStatusHistory::query()
            ->join('truck_appliances', 'truck_appliances.id', '=', 'inventory_status_histories.truck_appliance_id')
            ->join('users', 'users.id', '=', 'inventory_status_histories.user_id')
            ->whereBetween('inventory_status_histories.created_at', [$from, $to])
            ->whereIn('inventory_status_histories.status', ['Repair', 'Testing', 'Cleaning'])
            ->groupBy('users.id', 'users.name')
            ->select('users.id', 'users.name as username')
            ->selectRaw("COUNT(CASE WHEN inventory_status_histories.status = 'Repair' THEN 1 END) as units_repaired")
            ->selectRaw("COALESCE(SUM(CASE WHEN inventory_status_histories.status = 'Repair' THEN COALESCE(truck_appliances.msrp, 0) ELSE 0 END), 0) as msrp_repaired")
            ->selectRaw("COUNT(CASE WHEN inventory_status_histories.status = 'Testing' THEN 1 END) as units_tested")
            ->selectRaw("COALESCE(SUM(CASE WHEN inventory_status_histories.status = 'Testing' THEN COALESCE(truck_appliances.msrp, 0) ELSE 0 END), 0) as msrp_tested")
            ->selectRaw("COUNT(CASE WHEN inventory_status_histories.status = 'Cleaning' THEN 1 END) as units_cleaned")
            ->selectRaw("COALESCE(SUM(CASE WHEN inventory_status_histories.status = 'Cleaning' THEN COALESCE(truck_appliances.msrp, 0) ELSE 0 END), 0) as msrp_cleaned")
            ->selectRaw('COUNT(*) as total_units')
            ->selectRaw('COALESCE(SUM(COALESCE(truck_appliances.msrp, 0)), 0) as total_msrp')
            ->orderByDesc('total_msrp')
            ->get();
    }
}
