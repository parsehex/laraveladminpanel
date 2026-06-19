<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomSale;
use App\Models\InventoryStatusHistory;
use App\Models\Suggestion;
use App\Models\Truck;
use App\Models\TruckAppliance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $periodLabel] = $this->resolvePeriod($request);
        $canViewExecutiveDashboard = $this->canViewExecutiveDashboard($request->user());
        $productionRows = $canViewExecutiveDashboard ? $this->productionRows($from, $to) : collect();
        $productionTotals = [
            'total_units' => (int) $productionRows->sum('total_units'),
            'total_msrp' => (float) $productionRows->sum('total_msrp'),
        ];

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_units' => TruckAppliance::count(),
            'inventory_value' => TruckAppliance::query()
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '')
                        ->orWhereNotIn('status', ['Sold', 'Show Room']);
                })
                ->selectRaw("SUM(COALESCE(msrp, 0) + CASE WHEN status IN ('Demanufacture', 'Scrap') THEN -COALESCE(total_parts_cost, 0) ELSE COALESCE(total_parts_cost, 0) END) as value")
                ->value('value') ?: 0,
            'sold_units' => TruckAppliance::where('status', 'Sold')->count(),
            'sales_total' => (float) TruckAppliance::where('status', 'Sold')->sum('sold_price')
                + (float) CustomSale::sum('sold_price'),
        ];

        $users = User::query()
            ->whereIn('role', config('authorization.legacy_admin_role_values', ['admin']))
            ->orWhereHas('roles')
            ->orderBy('name')
            ->get();

        $activityRows = $users->map(function (User $user) use ($from, $to) {
            $trucksAdded = Truck::query()
                ->where('created_by', $user->id)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $unitsQuery = TruckAppliance::query()
                ->where('created_by', $user->id)
                ->whereBetween('created_at', [$from, $to]);

            return [
                'user' => $user,
                'trucks_added' => $trucksAdded,
                'trucks_deleted' => Truck::onlyTrashed()
                    ->where('updated_by', $user->id)
                    ->whereBetween('deleted_at', [$from, $to])
                    ->count(),
                'units_added' => (clone $unitsQuery)->count(),
                'units_deleted' => TruckAppliance::onlyTrashed()
                    ->where('updated_by', $user->id)
                    ->whereBetween('deleted_at', [$from, $to])
                    ->count(),
                'total_msrp_added' => (float) (clone $unitsQuery)->sum('msrp'),
                'units_tested' => $this->statusCount($user, 'Testing', $from, $to),
                'demanufactured' => $this->statusCount($user, 'Demanufacture', $from, $to),
                'repaired' => $this->statusCount($user, 'Repair', $from, $to),
                'showroom_sent' => $this->statusCount($user, 'Show Room', $from, $to),
                'sales_marked' => $this->statusCount($user, 'Sold', $from, $to),
            ];
        });

        $holdingForParts = TruckAppliance::query()
            ->with(['truck', 'model', 'category', 'statusHistories.user'])
            ->where('status', 'Holding for parts')
            ->latest('updated_at')
            ->take(25)
            ->get();

        $suggestions = Suggestion::query()
            ->with(['user', 'completedBy'])
            ->latest()
            ->take(25)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'activityRows' => $activityRows,
            'holdingForParts' => $holdingForParts,
            'suggestions' => $suggestions,
            'productionRows' => $productionRows,
            'productionTotals' => $productionTotals,
            'canViewExecutiveDashboard' => $canViewExecutiveDashboard,
            'period' => $request->get('period', 'weekly'),
            'periodLabel' => $periodLabel,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function storeSuggestion(Request $request)
    {
        $data = $request->validate([
            'suggestion' => ['required', 'string', 'max:2000'],
            'urgency' => ['required', 'in:low,normal,high'],
        ]);
        Suggestion::create([
            'user_id' => $request->user()->id,
            'username' => $request->user()->name,
            'suggestion' => $data['suggestion'],
            'urgency' => $data['urgency'],
            'status' => 'pending',
        ]);

        return back()->with('success', __('Suggestion submitted successfully.'));
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

        abort_unless($request->user()?->isStaff(), 403);

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
            'all' => [Carbon::create(1970, 1, 1)->startOfDay(), $now->copy()->endOfDay(), 'All time'],
            default => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay(), 'Last 7 days'],
        };
    }

    private function canViewExecutiveDashboard(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasRole('admin')
            || in_array($user->role, ['admin', 'Admin', 'Super Admin'], true);
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

    private function statusCount(User $user, string $status, Carbon $from, Carbon $to): int
    {
        return InventoryStatusHistory::query()
            ->where('user_id', $user->id)
            ->where('status', $status)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }
}
